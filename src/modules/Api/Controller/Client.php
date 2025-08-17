<?php

declare(strict_types=1);

namespace Box\Mod\Api\Controller;

use FOSSBilling\Config;
use FOSSBilling\Controller\ClientController;
use FOSSBilling\Environment;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Client extends ClientController
{
    private int|float|null $_requests_left = null;
    private $_api_config;
    private readonly Filesystem $filesystem;

    public function __construct()
    {
        parent::__construct();
        $this->filesystem = new Filesystem();
    }

    #[Route('/api/{role}/{class}/{method}', name: 'api_handler_get', methods: ['GET'])]
    public function get_method(Request $request, string $role, string $class, string $method): Response
    {
        $params = $request->query->all();
        $call = $class . '_' . $method;

        return $this->tryCall($role, $call, $params);
    }

    #[Route('/api/{role}/{class}/{method}', name: 'api_handler_post', methods: ['POST'])]
    public function post_method(Request $request, string $role, string $class, string $method): Response
    {
        $params = $request->request->all();

        // adding support for raw post input with json string
        if (empty($params)) {
            $content = $request->getContent();
            if (!empty($content)) {
                $params = json_decode($content, true);
            }
        }

        $call = $class . '_' . $method;
        return $this->tryCall($role, $call, $params);
    }

    private function tryCall($role, $call, $p): Response
    {
        try {
            return $this->_apiCall($role, $call, $p);
        } catch (\Exception $exc) {
            // Sentry by default only captures unhandled exceptions, so we need to manually capture these.
            \Sentry\captureException($exc);
            return $this->renderJson(null, $exc);
        }
    }

    private function _loadConfig()
    {
        if (is_null($this->_api_config)) {
            $this->_api_config = Config::getProperty('api', []);
        }
    }

    private function checkRateLimit($method = null)
    {
        if (in_array($this->_getIp(), $this->_api_config['rate_limit_whitelist'])) {
            return true;
        }

        $isLoginMethod = false;

        if ($method == 'staff_login' || $method == 'client_login') {
            $isLoginMethod = true;
            $rate_span = $this->_api_config['rate_span_login'];
            $rate_limit = $this->_api_config['rate_limit_login'];

            // 25 to 250ms delay to help prevent email enumeration.
            usleep(random_int(25000, 250000));
        } else {
            $rate_span = $this->_api_config['rate_span'];
            $rate_limit = $this->_api_config['rate_limit'];
        }

        $service = $this->di['mod_service']('api');
        $requests = $service->getRequestCount(time() - $rate_span, $this->_getIp(), $isLoginMethod);
        $this->_requests_left = $rate_limit - $requests;
        if ($this->_requests_left <= 0) {
            sleep($this->_api_config['throttle_delay']);
        }

        return true;
    }

    private function checkHttpReferer()
    {
        // snake oil: check request is from the same domain as FOSSBilling is installed if present
        $check_referer_header = isset($this->_api_config['require_referrer_header']) && (bool) $this->_api_config['require_referrer_header'];
        if ($check_referer_header) {
            $url = strtolower(SYSTEM_URL);
            $referer = isset($_SERVER['HTTP_REFERER']) ? strtolower($_SERVER['HTTP_REFERER']) : null;
            if (!$referer || !str_starts_with($referer, $url)) {
                throw new \FOSSBilling\InformationException('Invalid request. Make sure request origin is :from', [':from' => SYSTEM_URL], 1004);
            }
        }

        return true;
    }

    private function checkAllowedIps()
    {
        $ips = $this->_api_config['allowed_ips'];
        if (!empty($ips) && !in_array($this->_getIp(), $ips)) {
            throw new \FOSSBilling\InformationException('Unauthorized IP', null, 1002);
        }

        return true;
    }

    private function isRoleLoggedIn($role)
    {
        if ($role == 'client') {
            $this->di['is_client_logged'];
        }
        if ($role == 'admin') {
            $this->di['is_admin_logged'];
        }

        return true;
    }

    private function _apiCall($role, $method, $params): Response
    {
        $this->_loadConfig();
        $this->checkAllowedIps();

        $service = $this->di['mod_service']('api');
        $service->logRequest();
        $this->checkRateLimit($method);
        $this->checkHttpReferer();
        $this->isRoleAllowed($role);

        try {
            $this->isRoleLoggedIn($role);
            if ($role == 'client' || $role == 'admin') {
                $this->_checkCSRFToken();
            }
        } catch (\Exception) {
            $this->_tryTokenLogin();
        }

        $api = $this->di['api']($role);
        unset($params['CSRFToken']);
        $result = $api->$method($params);

        return $this->renderJson($result);
    }

    private function getAuth()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth_params = explode(':', base64_decode(substr($_SERVER['HTTP_AUTHORIZATION'], 6)));
            $_SERVER['PHP_AUTH_USER'] = $auth_params[0];
            unset($auth_params[0]);
            $_SERVER['PHP_AUTH_PW'] = implode('', $auth_params);
        }

        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            throw new \FOSSBilling\InformationException('Authentication Failed', null, 201);
        }

        if (!isset($_SERVER['PHP_AUTH_PW'])) {
            throw new \FOSSBilling\InformationException('Authentication Failed', null, 202);
        }

        if (empty($_SERVER['PHP_AUTH_PW'])) {
            throw new \FOSSBilling\InformationException('Authentication Failed', null, 206);
        }

        return [$_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']];
    }

    private function _tryTokenLogin()
    {
        [$username, $password] = $this->getAuth();

        switch ($username) {
            case 'client':
                $model = $this->di['db']->findOne('Client', 'api_token = ?', [$password]);
                if (!$model instanceof \Model_Client) {
                    throw new \FOSSBilling\InformationException('Authentication Failed', null, 204);
                }
                $this->di['session']->set('client_id', $model->id);
                break;

            case 'admin':
                $model = $this->di['db']->findOne('Admin', 'api_token = ?', [$password]);
                if (!$model instanceof \Model_Admin) {
                    throw new \FOSSBilling\InformationException('Authentication Failed', null, 205);
                }
                $sessionAdminArray = [
                    'id' => $model->id,
                    'email' => $model->email,
                    'name' => $model->name,
                    'role' => $model->role,
                ];
                $this->di['session']->set('admin', $sessionAdminArray);
                break;

            case 'guest':
            default:
                throw new \FOSSBilling\InformationException('Authentication Failed', null, 203);
        }
    }

    private function isRoleAllowed($role)
    {
        $allowed = ['guest', 'client', 'admin'];
        if (!in_array($role, $allowed)) {
            throw new \FOSSBilling\Exception('Unknown API call', [], 701);
        }
        return true;
    }

    public function renderJson($data = null, ?\Exception $e = null): JsonResponse
    {
        $this->_loadConfig();
        $response = new JsonResponse();
        $response->headers->set('Cache-Control', 'no-cache, must-revalidate');
        $response->headers->set('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT');
        $response->headers->set('X-FOSSBilling-Version', \FOSSBilling\Version::VERSION);
        $response->headers->set('X-RateLimit-Span', (string) $this->_api_config['rate_span']);
        $response->headers->set('X-RateLimit-Limit', (string) $this->_api_config['rate_limit']);
        $response->headers->set('X-RateLimit-Remaining', (string) $this->_requests_left);

        if ($e instanceof \Exception) {
            error_log($e->getMessage() . ' ' . $e->getCode());
            $code = $e->getCode() ?: 9999;
            $result = ['result' => null, 'error' => ['message' => $e->getMessage(), 'code' => $code]];
            $authFailed = [201, 202, 206, 204, 205, 203, 403, 1004, 1002];

            if (in_array($code, $authFailed)) {
                $response->setStatusCode(401);
            } elseif ($code == 701 || $code == 879) {
                $response->setStatusCode(400);
            } else {
                $response->setStatusCode(500);
            }
        } else {
            $result = ['result' => $data, 'error' => null];
        }

        $response->setData($result);
        return $response;
    }

    private function _getIp()
    {
        return $this->di['request']->getClientIp();
    }

    public function _checkCSRFToken()
    {
        $this->_loadConfig();
        $csrfPrevention = $this->_api_config['CSRFPrevention'] ?? true;
        if (!$csrfPrevention || Environment::isCLI()) {
            return true;
        }

        $request = $this->di['request'];
        $token = $request->get('CSRFToken') ?? $request->request->get('CSRFToken');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            $expectedToken = $request->cookies->get('PHPSESSID') ? hash('md5', $request->cookies->get('PHPSESSID')) : null;
        } else {
            $expectedToken = hash('md5', session_id());
        }

        if (str_contains($request->server->get('REQUEST_URI'), '/api/client/cart/checkout')) {
            return true;
        }

        if (!is_null($expectedToken) && $expectedToken !== $token) {
            throw new \FOSSBilling\InformationException('CSRF token invalid', null, 403);
        }
    }
}

<?php
declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Servicemaas\Controller;

class Admin implements \FOSSBilling\InjectionAwareInterface
{
    protected ?\Pimple\Container $di = null;

    public function setDi(\Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    public function fetchNavigation()
    {
        return [
            'group' => [
                'index' => 2000,
                'location' => 'maas',
                'label' => 'MAAS',
                'class' => 'maas',
            ],
            'subpages' => [
                [
                    'location' => 'maas',
                    'label' => 'Machines',
                    'uri' => $this->di['url']->adminLink('servicemaas'),
                    'index' => 100,
                    'class' => '',
                ],
                [
                    'location' => 'maas',
                    'label' => 'IP Management',
                    'uri' => $this->di['url']->adminLink('servicemaas/ip-management'),
                    'index' => 200,
                    'class' => '',
                ],
                [
                    'location' => 'system',
                    'label' => 'MAAS Settings',
                    'uri' => $this->di['url']->adminLink('extension/settings/servicemaas'),
                    'index' => 2100,
                    'class' => '',
                ],
            ],
        ];
    }

    public function register(\Box_App &$app)
    {
        $app->get('/servicemaas', 'get_index', [], static::class);
        $app->get('/servicemaas/ip-management', 'get_ip_management', [], static::class);

        $app->get('/api/admin/servicemaas/power_on', 'power_on', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->get('/api/admin/servicemaas/power_off', 'power_off', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->get('/api/admin/servicemaas/mark_broken', 'mark_broken', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->get('/api/admin/servicemaas/mark_fixed', 'mark_fixed', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->get('/api/admin/servicemaas/release', 'release', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
    }

    public function get_index(\Box_App $app)
    {
        $this->di['is_admin_logged'];

        $params = $this->di['mod_config']('servicemaas');
        $maasUrl = $params['maas_url'] ?? '';
        $apiKey = $params['maas_api_key'] ?? '';

        $machines = [];
        $error = null;

        if (!empty($maasUrl) && !empty($apiKey)) {
            try {
                require_once dirname(__DIR__) . '/MaasClient.php';
                $client = new \Box\Mod\Servicemaas\MaasClient($maasUrl, $apiKey);
                $machines = $client->get('/api/2.0/machines/');
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'MAAS API URL or API Key is not configured.';
        }

        return $app->render('mod_servicemaas_index', ['machines' => $machines, 'error' => $error]);
    }

    public function get_ip_management(\Box_App $app)
    {
        $this->di['is_admin_logged'];

        $params = $this->di['mod_config']('servicemaas');
        $maasUrl = $params['maas_url'] ?? '';
        $apiKey = $params['maas_api_key'] ?? '';

        $ip_addresses = [];
        $subnets = [];
        $ip_ranges = [];
        $error = null;

        if (!empty($maasUrl) && !empty($apiKey)) {
            try {
                require_once dirname(__DIR__) . '/MaasClient.php';
                $client = new \Box\Mod\Servicemaas\MaasClient($maasUrl, $apiKey);
                $ip_addresses = $client->get('/api/2.0/ipaddresses/');
                $subnets = $client->get('/api/2.0/subnets/');
                $ip_ranges = $client->get('/api/2.0/ipranges/');
            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        } else {
            $error = 'MAAS API URL or API Key is not configured.';
        }

        return $app->render('mod_servicemaas_ip_management', [
            'ip_addresses' => $ip_addresses,
            'subnets' => $subnets,
            'ip_ranges' => $ip_ranges,
            'error' => $error
        ]);
    }
}

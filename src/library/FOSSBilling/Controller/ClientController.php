<?php

declare(strict_types=1);

namespace FOSSBilling\Controller;

use FOSSBilling\Environment;
use FOSSBilling\TwigExtensions\DebugBar;
use Symfony\Component\HttpFoundation\Response;
use Twig\Profiler\Profile;
use DebugBar\Bridge\NamespacedTwigProfileCollector;
use Symfony\Component\Filesystem\Path;

class ClientController extends BaseController
{
    public function render(string $view, array $parameters = [], Response $response = null): Response
    {
        $twig = $this->getTwig();
        $content = $twig->render($view, $parameters);

        if (null === $response) {
            $response = new Response();
        }
        $response->setContent($content);

        return $response;
    }

    private function getTwig(): \Twig\Environment
    {
        $service = $this->di['mod_service']('theme');
        $code = $service->getCurrentClientAreaThemeCode();
        $theme = $service->getTheme($code);
        $settings = $service->getThemeSettings($theme);

        $loader = new \Box_TwigLoader(
            [
                'mods' => PATH_MODS,
                'theme' => Path::join(PATH_THEMES, $code),
                'type' => 'client',
            ]
        );

        $twig = $this->di['twig'];
        $twig->setLoader($loader);

        $twig->addGlobal('current_theme', $code);
        $twig->addGlobal('settings', $settings);

        if (Environment::isDevelopment()) {
            $profile = new Profile();
            $twig->addExtension(new \Twig\Extension\ProfilerExtension($profile));
            // TODO: Re-add debug bar
            // $collector = new NamespacedTwigProfileCollector($profile);
            // if (!$this->debugBar->hasCollector($collector->getName())) {
            //     $this->debugBar->addCollector($collector);
            // }
        }

        // $twig->addExtension(new DebugBar($this->getDebugBar()));

        if ($this->di['auth']->isClientLoggedIn()) {
            $twig->addGlobal('client', $this->di['api_client']);
        }

        if ($this->di['auth']->isAdminLoggedIn()) {
            $twig->addGlobal('admin', $this->di['api_admin']);
        }

        return $twig;
    }
}

<?php

declare(strict_types=1);

namespace FOSSBilling\Controller;

use FOSSBilling\Environment;
use FOSSBilling\TwigExtensions\DebugBar;
use Symfony\Component\HttpFoundation\Response;
use Twig\Profiler\Profile;
use DebugBar\Bridge\NamespacedTwigProfileCollector;
use Symfony\Component\Filesystem\Path;

class AdminController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
        // Every admin controller requires the user to be logged in.
        $this->di['is_admin_logged'];
    }

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
        $loader = new \Box_TwigLoader(
            [
                'mods' => PATH_MODS,
                'theme' => PATH_THEMES . DIRECTORY_SEPARATOR . 'admin_default',
                'type' => 'admin',
            ]
        );

        $twig = $this->di['twig'];
        $twig->setLoader($loader);

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

        $twig->addGlobal('admin', $this->di['api_admin']);

        return $twig;
    }
}

<?php

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('FOSSBilling\\', '../library/FOSSBilling/*')
        ->exclude('../library/FOSSBilling/{ErrorPage.php,ExtensionManager.php,i18n.php,Update.php}');

    $services->load('Box\\Mod\\', '../modules/*/')
        ->exclude('../modules/*/Controller/');
};

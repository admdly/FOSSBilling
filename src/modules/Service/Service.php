<?php

namespace Box\Mod\Service;

use FOSSBilling\InjectionAwareInterface;

class Service implements InjectionAwareInterface
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

    public function install()
    {
        $sql = '
        CREATE TABLE IF NOT EXISTS `service_apikey` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT UNIQUE,
            `client_id` bigint(20) NOT NULL,
            `api_key` varchar(255),
            `config` text NOT NULL,
            `created_at` datetime,
            `updated_at` datetime,
            PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;';
        $this->di['db']->exec($sql);

        return true;
    }

    public function uninstall()
    {
        $this->di['db']->exec('DROP TABLE IF EXISTS `service_apikey`');

        return true;
    }

    public function getService($name)
    {
        $serviceName = ucfirst(strtolower($name));
        $class = 'FOSSBilling\\Extension\\Service\\' . $serviceName . '\\Service';
        $file = 'src/extensions/services/' . $serviceName . '/Service.php';
        if (file_exists($file)) {
            include_once $file;
            $service = new $class();
            $service->setDi($this->di);
            return $service;
        } else {
            return null;
        }
    }
}

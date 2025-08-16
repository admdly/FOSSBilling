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

namespace Box\Mod\Servicemaas;

class Service implements \FOSSBilling\InjectionAwareInterface
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
        $this->di['db']->exec("
            CREATE TABLE IF NOT EXISTS `service_maas` (
                `id` bigint(20) NOT NULL AUTO_INCREMENT,
                `client_id` bigint(20) NOT NULL,
                `order_id` bigint(20) NOT NULL,
                `system_id` varchar(255) DEFAULT NULL,
                `hostname` varchar(255) DEFAULT NULL,
                `status` varchar(255) DEFAULT 'pending',
                `created_at` varchar(35) DEFAULT NULL,
                `updated_at` varchar(35) DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `client_id_idx` (`client_id`),
                KEY `order_id_idx` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function uninstall()
    {
        $this->di['db']->exec("DROP TABLE IF EXISTS `service_maas`;");
    }

    public function action_create(\Model_ClientOrder $order)
    {
        $model = $this->di['db']->dispense('Servicemaas');
        $model->client_id = $order->client_id;
        $model->order_id = $order->id;
        $model->status = 'pending';
        $model->created_at = date('Y-m-d H:i:s');
        $model->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($model);
        return $model;
    }

    public function action_activate(\Model_ClientOrder $order)
    {
        $orderService = $this->di['mod_service']('order');
        $service = $orderService->getOrderService($order);

        if (!$service) {
            throw new \FOSSBilling\Exception('Order has no service');
        }

        $config = $order->config;
        $params = $this->di['mod_config']('servicemaas');
        $maasUrl = $params['maas_url'] ?? '';
        $apiKey = $params['maas_api_key'] ?? '';

        if (empty($maasUrl) || empty($apiKey)) {
            throw new \FOSSBilling\Exception('MAAS module is not configured.');
        }

        require_once __DIR__ . '/MaasClient.php';
        $client = new \Box\Mod\Servicemaas\MaasClient($maasUrl, $apiKey);

        // 1. Allocate machine
        $allocation_params = [
            'cpu_count' => $config['cpu_count'] ?? null,
            'mem' => $config['mem'] ?? null,
            'storage' => $config['storage'] ?? null,
            'distro_series' => $config['distro_series'] ?? null,
        ];
        $machine = $client->post('/api/2.0/machines/op-allocate/', ['form_params' => array_filter($allocation_params)]);
        $systemId = $machine['system_id'];

        // 2. Deploy machine
        $deploy_params = [
            'distro_series' => $config['distro_series'] ?? null,
        ];
        $client->post("/api/2.0/machines/{$systemId}/op-deploy/", ['form_params' => array_filter($deploy_params)]);

        // 3. Update service
        $service->system_id = $systemId;
        $service->hostname = $machine['hostname'];
        $service->status = 'active';
        $service->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($service);

        return true;
    }

    public function action_cancel(\Model_ClientOrder $order)
    {
        $orderService = $this->di['mod_service']('order');
        $service = $orderService->getOrderService($order);

        $client = $this->_get_client();
        $client->post("/api/2.0/machines/{$service->system_id}/op-release/");

        $service->status = 'canceled';
        $service->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($service);

        return true;
    }

    public function action_delete(\Model_ClientOrder $order)
    {
        $orderService = $this->di['mod_service']('order');
        $service = $orderService->getOrderService($order);

        if ($service->system_id) {
            $client = $this->_get_client();
            $client->post("/api/2.0/machines/{$service->system_id}/op-release/");
        }

        $this->di['db']->trash($service);

        return true;
    }

    private function _get_client()
    {
        $params = $this->di['mod_config']('servicemaas');
        $maasUrl = $params['maas_url'] ?? '';
        $apiKey = $params['maas_api_key'] ?? '';

        if (empty($maasUrl) || empty($apiKey)) {
            throw new \FOSSBilling\Exception('MAAS module is not configured.');
        }

        require_once __DIR__ . '/MaasClient.php';
        return new \Box\Mod\Servicemaas\MaasClient($maasUrl, $apiKey);
    }
}

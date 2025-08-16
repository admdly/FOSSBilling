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

namespace Box\Mod\Servicemaas\Api;

class Client extends \Api_Abstract
{
    private function _get_client()
    {
        $params = $this->di['mod_config']('servicemaas');
        $maasUrl = $params['maas_url'] ?? '';
        $apiKey = $params['maas_api_key'] ?? '';

        if (empty($maasUrl) || empty($apiKey)) {
            throw new \FOSSBilling\Exception('MAAS module is not configured.');
        }

        require_once dirname(__DIR__) . '/MaasClient.php';
        return new \Box\Mod\Servicemaas\MaasClient($maasUrl, $apiKey);
    }

    private function _get_service($order_id)
    {
        $order = $this->di['db']->getExistingModelById('ClientOrder', $order_id, 'Order not found');
        $orderService = $this->di['mod_service']('order');
        $s = $orderService->getOrderService($order);
        if (!$s) {
            throw new \FOSSBilling\Exception('Order is not activated');
        }
        return $s;
    }

    public function power_on($data)
    {
        $s = $this->_get_service($data['order_id']);
        $systemId = $s->system_id;
        $client = $this->_get_client();
        $client->post("/api/2.0/machines/{$systemId}/op-power_on/");
        return true;
    }

    public function power_off($data)
    {
        $s = $this->_get_service($data['order_id']);
        $systemId = $s->system_id;
        $client = $this->_get_client();
        $client->post("/api/2.0/machines/{$systemId}/op-power_off/");
        return true;
    }

    public function reboot($data)
    {
        $this->power_off($data);
        sleep(2); // Wait for the machine to power off
        $this->power_on($data);
        return true;
    }

    public function reinstall($data)
    {
        $s = $this->_get_service($data['order_id']);
        $systemId = $s->system_id;
        $client = $this->_get_client();
        $client->post("/api/2.0/machines/{$systemId}/op-deploy/");
        return true;
    }
}

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

class Admin extends \Api_Abstract
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

    public function power_on($data)
    {
        $systemId = $data['system_id'];
        $client = $this->_get_client();
        $client->post("/api/2.0/machines/{$systemId}/op-power_on/");
        return true;
    }

    public function power_off($data)
    {
        $systemId = $data['system_id'];
        $client = $this->_get_client();
        $client->post("/api/2.0/machines/{$systemId}/op-power_off/");
        return true;
    }

    public function mark_broken($data)
    {
        $systemId = $data['system_id'];
        $client = $this->_get_client();
        $client->post("/api/2.0/machines/{$systemId}/op-mark_broken/");
        return true;
    }

    public function mark_fixed($data)
    {
        $systemId = $data['system_id'];
        $client = $this->_get_client();
        $client->post("/api/2.0/machines/{$systemId}/op-mark_fixed/");
        return true;
    }

    public function release($data)
    {
        $systemId = $data['system_id'];
        $client = $this->_get_client();
        $client->post("/api/2.0/machines/{$systemId}/op-release/");
        return true;
    }
}

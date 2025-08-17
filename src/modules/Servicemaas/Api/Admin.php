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

    public function machine_get($data)
    {
        $systemId = $data['system_id'];
        $client = $this->_get_client();
        return $client->get("/api/2.0/machines/{$systemId}/");
    }

    public function machine_create($data)
    {
        $client = $this->_get_client();
        $params = [
            'hostname' => $data['hostname'],
            'architecture' => $data['architecture'],
            'mac_addresses' => $data['mac_addresses'],
            'power_type' => $data['power_type'],
        ];
        if(isset($data['power_parameters'])) {
            $power_params = json_decode($data['power_parameters'], true);
            foreach($power_params as $key => $value) {
                $params['power_parameters_'.$key] = $value;
            }
        }
        $client->post('/api/2.0/machines/', ['form_params' => $params]);
        return true;
    }

    public function machine_update($data)
    {
        $systemId = $data['system_id'];
        $client = $this->_get_client();
        $params = [
            'hostname' => $data['hostname'],
            'architecture' => $data['architecture'],
            'mac_addresses' => $data['mac_addresses'],
            'power_type' => $data['power_type'],
        ];
        if(isset($data['power_parameters'])) {
            $power_params = json_decode($data['power_parameters'], true);
            foreach($power_params as $key => $value) {
                $params['power_parameters_'.$key] = $value;
            }
        }
        $client->put("/api/2.0/machines/{$systemId}/", ['form_params' => $params]);
        return true;
    }

    public function machine_delete($data)
    {
        $systemId = $data['system_id'];
        $client = $this->_get_client();
        $client->delete("/api/2.0/machines/{$systemId}/");
        return true;
    }

    public function subnet_get($data)
    {
        $id = $data['id'];
        $client = $this->_get_client();
        return $client->get("/api/2.0/subnets/{$id}/");
    }

    public function subnet_create($data)
    {
        $client = $this->_get_client();
        $params = [
            'name' => $data['name'],
            'cidr' => $data['cidr'],
            'gateway_ip' => $data['gateway_ip'],
            'dns_servers' => $data['dns_servers'],
            'vlan' => $data['vlan'],
        ];
        $client->post('/api/2.0/subnets/', ['form_params' => $params]);
        return true;
    }

    public function subnet_update($data)
    {
        $id = $data['id'];
        $client = $this->_get_client();
        $params = [
            'name' => $data['name'],
            'gateway_ip' => $data['gateway_ip'],
            'dns_servers' => $data['dns_servers'],
        ];
        $client->put("/api/2.0/subnets/{$id}/", ['form_params' => $params]);
        return true;
    }

    public function subnet_delete($data)
    {
        $id = $data['id'];
        $client = $this->_get_client();
        $client->delete("/api/2.0/subnets/{$id}/");
        return true;
    }

    public function iprange_get($data)
    {
        $id = $data['id'];
        $client = $this->_get_client();
        return $client->get("/api/2.0/ipranges/{$id}/");
    }

    public function iprange_create($data)
    {
        $client = $this->_get_client();
        $params = [
            'type' => $data['type'],
            'start_ip' => $data['start_ip'],
            'end_ip' => $data['end_ip'],
            'comment' => $data['comment'],
            'subnet' => $data['subnet'],
        ];
        $client->post('/api/2.0/ipranges/', ['form_params' => $params]);
        return true;
    }

    public function iprange_update($data)
    {
        $id = $data['id'];
        $client = $this->_get_client();
        $params = [
            'start_ip' => $data['start_ip'],
            'end_ip' => $data['end_ip'],
            'comment' => $data['comment'],
        ];
        $client->put("/api/2.0/ipranges/{$id}/", ['form_params' => $params]);
        return true;
    }

    public function iprange_delete($data)
    {
        $id = $data['id'];
        $client = $this->_get_client();
        $client->delete("/api/2.0/ipranges/{$id}/");
        return true;
    }

    public function ip_reserve($data)
    {
        $client = $this->_get_client();
        $params = [
            'ip' => $data['ip_address'],
            'subnet' => $data['subnet'],
            'hostname' => $data['hostname'],
        ];
        $client->post('/api/2.0/ipaddresses/op-reserve/', ['form_params' => $params]);
        return true;
    }

    public function ip_release($data)
    {
        $client = $this->_get_client();
        $params = [
            'ip' => $data['ip'],
        ];
        $client->post('/api/2.0/ipaddresses/op-release/', ['form_params' => $params]);
        return true;
    }
}

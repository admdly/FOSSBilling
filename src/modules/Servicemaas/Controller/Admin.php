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

        $app->get('/servicemaas/machine/new', 'get_machine_new', [], static::class);
        $app->get('/servicemaas/machine/:system_id', 'get_machine', ['system_id' => '[a-zA-Z0-9]+'], static::class);
        $app->post('/api/admin/servicemaas/machine_create', 'machine_create', [], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->post('/api/admin/servicemaas/machine_update', 'machine_update', [], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->post('/api/admin/servicemaas/machine_delete', 'machine_delete', [], \Box\Mod\Servicemaas\Api\Admin::class);

        $app->get('/servicemaas/subnet/new', 'get_subnet_new', [], static::class);
        $app->get('/servicemaas/subnet/:id', 'get_subnet', ['id' => '[0-9]+'], static::class);
        $app->post('/api/admin/servicemaas/subnet_create', 'subnet_create', [], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->post('/api/admin/servicemaas/subnet_update', 'subnet_update', [], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->post('/api/admin/servicemaas/subnet_delete', 'subnet_delete', [], \Box\Mod\Servicemaas\Api\Admin::class);

        $app->get('/servicemaas/iprange/new', 'get_iprange_new', [], static::class);
        $app->get('/servicemaas/iprange/:id', 'get_iprange', ['id' => '[0-9]+'], static::class);
        $app->post('/api/admin/servicemaas/iprange_create', 'iprange_create', [], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->post('/api/admin/servicemaas/iprange_update', 'iprange_update', [], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->post('/api/admin/servicemaas/iprange_delete', 'iprange_delete', [], \Box\Mod\Servicemaas\Api\Admin::class);

        $app->get('/servicemaas/ip/reserve', 'get_ip_reserve', [], static::class);
        $app->post('/api/admin/servicemaas/ip_reserve', 'ip_reserve', [], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->post('/api/admin/servicemaas/ip_release', 'ip_release', [], \Box\Mod\Servicemaas\Api\Admin::class);

        $app->get('/api/admin/servicemaas/power_on', 'power_on', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->get('/api/admin/servicemaas/power_off', 'power_off', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->get('/api/admin/servicemaas/mark_broken', 'mark_broken', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->get('/api/admin/servicemaas/mark_fixed', 'mark_fixed', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
        $app->get('/api/admin/servicemaas/release', 'release', ['system_id' => 'string'], \Box\Mod\Servicemaas\Api\Admin::class);
    }

    public function get_machine_new(\Box_App $app)
    {
        $this->di['is_admin_logged'];
        return $app->render('mod_servicemaas_machine_edit', ['machine' => []]);
    }

    public function get_machine(\Box_App $app, $system_id)
    {
        $this->di['is_admin_logged'];
        $api = $this->di['api_admin'];
        $machine = $api->servicemaas_machine_get(['system_id' => $system_id]);
        return $app->render('mod_servicemaas_machine_edit', ['machine' => $machine]);
    }

    public function get_subnet_new(\Box_App $app)
    {
        $this->di['is_admin_logged'];
        return $app->render('mod_servicemaas_subnet_edit', ['subnet' => []]);
    }

    public function get_subnet(\Box_App $app, $id)
    {
        $this->di['is_admin_logged'];
        $api = $this->di['api_admin'];
        $subnet = $api->servicemaas_subnet_get(['id' => $id]);
        return $app->render('mod_servicemaas_subnet_edit', ['subnet' => $subnet]);
    }

    public function get_iprange_new(\Box_App $app)
    {
        $this->di['is_admin_logged'];
        return $app->render('mod_servicemaas_iprange_edit', ['range' => []]);
    }

    public function get_iprange(\Box_App $app, $id)
    {
        $this->di['is_admin_logged'];
        $api = $this->di['api_admin'];
        $range = $api->servicemaas_iprange_get(['id' => $id]);
        return $app->render('mod_servicemaas_iprange_edit', ['range' => $range]);
    }

    public function get_ip_reserve(\Box_App $app)
    {
        $this->di['is_admin_logged'];
        return $app->render('mod_servicemaas_ip_reserve');
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

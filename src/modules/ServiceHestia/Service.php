<?php
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\ServiceHestia;

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

    public function _get_manager_and_account(\Model_ClientOrder $order)
    {
        $orderService = $this->di['mod_service']('order');
        $model = $orderService->getOrderService($order);
        if (!$model instanceof \Model_ServiceHosting) {
            throw new \FOSSBilling\Exception('Order has no active hosting service');
        }

        $hp = $this->di['db']->getExistingModelById('ServiceHostingHp', $model->service_hosting_hp_id, 'Hosting plan not found');
        $server = $this->di['db']->getExistingModelById('ServiceHostingServer', $model->service_hosting_server_id, 'Server not found');
        $client = $this->di['db']->getExistingModelById('Client', $model->client_id, 'Client not found');

        $server_client = new \Server_Client();
        $server_client
            ->setEmail($client->email)
            ->setFirstName($client->first_name)
            ->setLastName($client->last_name);

        $package = $this->di['mod_service']('servicehosting')->getServerPackage($hp);

        $server_account = new \Server_Account();
        $server_account
            ->setClient($server_client)
            ->setPackage($package)
            ->setUsername($model->username)
            ->setDomain($model->sld . $model->tld);

        $serviceHostingService = $this->di['mod_service']('servicehosting');
        $adapter = $serviceHostingService->getServerManagerWithLog($server, $order);

        return [$adapter, $server_account];
    }
}

<?php
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\ServiceHestia\Api;

class Client extends \Api_Abstract
{
    /**
     * Get list of web domains
     */
    public function list_web_domains($data)
    {
        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->listWebDomains($account);
    }

    /**
     * Add web domain
     */
    public function add_web_domain($data)
    {
        $required = [
            'domain' => 'Domain name is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->addWebDomain($account, $data['domain']);
    }

    /**
     * Delete web domain
     */
    public function delete_web_domain($data)
    {
        $required = [
            'domain' => 'Domain name is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->deleteWebDomain($account, $data['domain']);
    }

    /**
     * Add web domain alias
     */
    public function add_web_domain_alias($data)
    {
        $required = [
            'domain' => 'Domain name is required',
            'alias'  => 'Alias is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->addWebDomainAlias($account, $data['domain'], $data['alias']);
    }

    /**
     * Delete web domain alias
     */
    public function delete_web_domain_alias($data)
    {
        $required = [
            'domain' => 'Domain name is required',
            'alias'  => 'Alias is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->deleteWebDomainAlias($account, $data['domain'], $data['alias']);
    }

    /**
     * Add Let's Encrypt certificate
     */
    public function add_lets_encrypt($data)
    {
        $required = [
            'domain' => 'Domain name is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->addLetsEncryptDomain($account, $data['domain']);
    }

    /**
     * Delete Let's Encrypt certificate
     */
    public function delete_lets_encrypt($data)
    {
        $required = [
            'domain' => 'Domain name is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->deleteLetsEncryptDomain($account, $data['domain']);
    }

    /**
     * List available backend templates
     */
    public function list_web_backend_templates($data)
    {
        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->listWebBackendTemplates();
    }

    /**
     * Change backend template
     */
    public function change_backend_tpl($data)
    {
        $required = [
            'domain'   => 'Domain name is required',
            'template' => 'Backend template is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->changeWebDomainBackendTpl($account, $data['domain'], $data['template']);
    }

    public function list_cron_jobs($data)
    {
        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->listCronJobs($account);
    }

    public function add_cron_job($data)
    {
        $required = [
            'command'  => 'Command is required',
            'schedule' => 'Schedule is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->addCronJob($account, $data['command'], $data['schedule']);
    }

    public function delete_cron_job($data)
    {
        $required = [
            'job_id' => 'Job ID is required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->_getServiceOrder($data);
        [$manager, $account] = $this->getService()->_get_manager_and_account($order);
        return $manager->deleteCronJob($account, $data['job_id']);
    }

    private function _getServiceOrder($data)
    {
        if (!isset($data['order_id'])) {
            throw new \FOSSBilling\Exception('order_id is required');
        }

        $order = $this->di['db']->getExistingModelById('ClientOrder', $data['order_id'], 'Order not found');

        $client_id = $this->getIdentity()->id;
        if ($order->client_id != $client_id) {
            throw new \FOSSBilling\Exception('Order does not belong to the client');
        }

        return $order;
    }
}

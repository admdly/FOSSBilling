<?php

declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Servicecustom;

/**
 * Custom service management and product management API.
 */
class Api extends \Api_Abstract
{
    /**
     * Update custom service configuration.
     *
     * @admin
     *
     * @return bool
     */
    public function update($data)
    {
        $this->assertAdmin();
        if (!isset($data['order_id'])) {
            throw new \FOSSBilling\Exception('Order ID is required');
        }
        if (isset($data['config']) && is_array($data['config'])) {
            $this->getService()->updateConfig($data['order_id'], $data['config']);
        }
        return true;
    }

    /**
     * Returns SEO information. When the pings were was last sent.
     *
     * @admin
     *
     * @return array
     */
    public function info($data)
    {
        $this->assertAdmin();
        return $this->getService()->getInfo();
    }

    /**
     * Ping every search engine to let them know that the sitemap has been updated.
     *
     * @admin
     *
     * @return bool
     */
    public function ping_all()
    {
        $this->assertAdmin();
        $extensionService = $this->di['mod_service']('extension');
        $config = $extensionService->getConfig('mod_seo');
        return $this->getService()->pingSitemap($config, true);
    }

    /**
     * Universal method to call method from plugin.
     * Pass any other params and they will be passed to plugin.
     *
     * @client
     *
     * @throws \FOSSBilling\Exception
     */
    public function __call($name, $arguments)
    {
        $this->assertAuthenticated();
        if (!isset($arguments[0])) {
            throw new \FOSSBilling\Exception('API call is missing arguments', null, 7103);
        }
        $data = $arguments[0];
        if (!isset($data['order_id'])) {
            throw new \FOSSBilling\Exception('Order ID is required');
        }
        $model = $this->getService()->getServiceCustomByOrderId($data['order_id']);
        return $this->getService()->customCall($model, $name, $data);
    }
}

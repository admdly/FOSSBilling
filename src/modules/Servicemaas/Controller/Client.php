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

class Client implements \FOSSBilling\InjectionAwareInterface
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

    public function register(\Box_App &$app)
    {
        $app->get('/servicemaas/manage/:order_id', 'get_manage', ['order_id' => '[0-9]+'], static::class);
    }

    public function get_manage(\Box_App $app, $order_id)
    {
        $api = $this->di['api_client'];
        $order = $api->order_get(['id' => $order_id]);
        return $app->render('mod_servicemaas_manage', ['order' => $order]);
    }
}

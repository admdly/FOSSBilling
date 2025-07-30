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

namespace Box\Mod\Servicedownloadable;

/**
 * Downloadable service management API (merged Admin & Client).
 */
class Api extends \Api_Abstract
{
    /**
     * Upload file to product. Uses $_FILES array so make sure your form is enctype="multipart/form-data".
     *
     * @admin
     *
     * @return bool
     */
    public function upload($data)
    {
        $this->assertAdmin();
        $required = [
            'id' => 'Product ID is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('Product', $data['id'], 'Product not found');
        $request = $this->di['request'];
        if (!$request->files->has('file_data')) {
            throw new \FOSSBilling\Exception('File was not uploaded.');
        }
        $service = $this->getService();
        return $service->uploadProductFile($model);
    }

    /**
     * Update downloadable product order with new file. This will change only this order file.
     * Uses $_FILES array so make sure your form is enctype="multipart/form-data"
     *
     * @admin
     *
     * @return bool
     */
    public function update($data)
    {
        $this->assertAdmin();
        $required = [
            'order_id' => 'Order ID is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $order = $this->di['db']->getExistingModelById('ClientOrder', $data['order_id'], 'Order not found');
        $orderService = $this->di['mod_service']('order');
        $serviceDownloadable = $orderService->getOrderService($order);
        if (!$serviceDownloadable instanceof \Model_ServiceDownloadable) {
            throw new \FOSSBilling\Exception('Order is not activated');
        }
        $service = $this->getService();
        return $service->updateProductFile($serviceDownloadable, $order);
    }

    /**
     * Save configuration for product.
     *
     * @admin
     *
     * @return bool
     */
    public function config_save($data)
    {
        $this->assertAdmin();
        $required = [
            'id' => 'Product ID is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('Product', $data['id'], 'Product not found');
        $service = $this->getService();
        return $service->saveProductConfig($model, $data);
    }

    /**
     * Sends file attached to order as attachment.
     *
     * @client
     *
     * @return bool
     */
    public function send_file($data)
    {
        $this->assertClient();
        $identity = $this->getIdentity();
        if (!isset($data['order_id'])) {
            throw new \FOSSBilling\Exception('Order ID is required');
        }
        $order = $this->di['db']->findOne('ClientOrder', 'id = :id AND client_id = :client_id', [':id' => $data['order_id'], ':client_id' => $identity->id]);
        if (!$order instanceof \Model_ClientOrder) {
            throw new \FOSSBilling\Exception('Order not found');
        }
        $orderService = $this->di['mod_service']('order');
        $s = $orderService->getOrderService($order);
        if (!$s instanceof \Model_ServiceDownloadable || $order->status !== 'active') {
            throw new \FOSSBilling\Exception('Order is not activated');
        }
        $service = $this->getService();
        return (bool) $service->sendFile($s);
    }
}

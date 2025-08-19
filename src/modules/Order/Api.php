<?php
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Order;

class Api extends \Api_Abstract
{
    /**
     * Get order details.
     *
     * @return array
     */
    public function get($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $deep = isset($data['deep']) ? (bool) $data['deep'] : true;
            $order = $this->_getOrderForAdmin($data);
            return $this->getService()->toApiArray($order, $deep, $this->getIdentity());
        }

        $this->requireContext(['client']);
        $model = $this->_getOrderForClient($data);
        return $this->getService()->toApiArray($model);
    }

    /**
     * Return paginated list of orders.
     *
     * @return array
     */
    public function get_list($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $orderConfig = $this->di['mod']('order')->getConfig();
            $data['hide_addons'] = (isset($orderConfig['show_addons']) && $orderConfig['show_addons']) ? 0 : 1;
            [$sql, $params] = $this->getService()->getSearchQuery($data);
            $paginator = $this->di['pager'];
            $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
            $resultSet = $paginator->getPaginatedResultSet($sql, $params, $per_page);

            foreach ($resultSet['list'] as $key => $result) {
                $orderObj = $this->di['db']->getExistingModelById('ClientOrder', $result['id'], 'Order not found');
                $resultSet['list'][$key] = $this->getService()->toApiArray($orderObj, true, $this->getIdentity());
            }
            return $resultSet;
        }

        $this->requireContext(['client']);
        $identity = $this->getIdentity();
        $data['client_id'] = $identity->id;
        if (isset($data['expiring'])) {
            [$query, $bindings] = $this->getService()->getSoonExpiringActiveOrdersQuery($data);
        } else {
            [$query, $bindings] = $this->getService()->getSearchQuery($data);
        }
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($query, $bindings, $per_page);

        foreach ($pager['list'] as $key => $item) {
            $order = $this->di['db']->getExistingModelById('ClientOrder', $item['id'], 'Client order not found');
            $pager['list'][$key] = $this->getService()->toApiArray($order);
        }

        return $pager;
    }

    /**
     * Place new order for client. Admin is able to order disabled products.
     *
     * @return array
     */
    public function create($data)
    {
        $required = [
            'client_id' => 'Client id not passed',
            'product_id' => 'Product id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $client = $this->di['db']->getExistingModelById('Client', $data['client_id'], 'Client not found');
        $product = $this->di['db']->getExistingModelById('Product', $data['product_id'], 'Product not found');

        return $this->getService()->createOrder($client, $product, $data);
    }

    /**
     * Update order settings.
     *
     * @return bool
     */
    public function update($data)
    {
        $order = $this->_getOrderForAdmin($data);

        return $this->getService()->updateOrder($order, $data);
    }

    /**
     * Activate order depending on current status.
     *
     * @return bool
     */
    public function activate($data)
    {
        $order = $this->_getOrderForAdmin($data);

        return $this->getService()->activateOrder($order, $data);
    }

    /**
     * Activate order depending on current status.
     *
     * @return bool
     */
    public function renew($data)
    {
        $order = $this->_getOrderForAdmin($data);

        if ($order->status == \Model_ClientOrder::STATUS_PENDING_SETUP || $order->status == \Model_ClientOrder::STATUS_FAILED_SETUP) {
            return $this->activate($data);
        }

        return $this->getService()->renewOrder($order, $data);
    }

    /**
     * Suspend order.
     *
     * @return bool
     */
    public function suspend($data)
    {
        $order = $this->_getOrderForAdmin($data);
        $skip_event = isset($data['skip_event']) && (bool) $data['skip_event'];
        $reason = $data['reason'] ?? null;

        return $this->getService()->suspendFromOrder($order, $reason, $skip_event);
    }

    /**
     * Unsuspend suspended order.
     *
     * @return bool
     */
    public function unsuspend($data)
    {
        $order = $this->_getOrderForAdmin($data);
        if ($order->status != \Model_ClientOrder::STATUS_SUSPENDED) {
            throw new \FOSSBilling\InformationException('Only suspended orders can be unsuspended');
        }

        return $this->getService()->unsuspendFromOrder($order);
    }

    /**
     * Cancel order.
     *
     * @return bool
     */
    public function cancel($data)
    {
        $order = $this->_getOrderForAdmin($data);
        $skip_event = isset($data['skip_event']) && (bool) $data['skip_event'];
        $reason = $data['reason'] ?? null;

        return $this->getService()->cancelFromOrder($order, $reason, $skip_event);
    }

    /**
     * Uncancel canceled order.
     *
     * @return bool
     */
    public function uncancel($data)
    {
        $order = $this->_getOrderForAdmin($data);
        if ($order->status != \Model_ClientOrder::STATUS_CANCELED) {
            throw new \FOSSBilling\InformationException('Only canceled orders can be uncanceled');
        }

        return $this->getService()->uncancelFromOrder($order);
    }

    /**
     * Delete order.
     *
     * @return bool
     */
    public function delete($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $order = $this->_getOrderForAdmin($data);
            $delete_addons = isset($data['delete_addons']) && (bool) $data['delete_addons'];
            $forceDelete = (bool) ($data['force_delete'] ?? false);

            if ($delete_addons) {
                $list = $this->getService()->getOrderAddonsList($order);
                foreach ($list as $addon) {
                    $this->getService()->deleteFromOrder($addon, $forceDelete);
                }
            }
            return $this->getService()->deleteFromOrder($order, $forceDelete);
        }

        $this->requireContext(['client']);
        $model = $this->_getOrderForClient($data);
        if (!in_array($model->status, [\Model_ClientOrder::STATUS_PENDING_SETUP, \Model_ClientOrder::STATUS_FAILED_SETUP])) {
            throw new \FOSSBilling\InformationException('Only pending and failed setup orders can be deleted.');
        }

        return $this->getService()->deleteFromOrder($model);
    }

    /**
     * Suspend all expired orders.
     *
     * @return bool
     */
    public function batch_suspend_expired($data)
    {
        return $this->getService()->batchSuspendExpired();
    }

    /**
     * Cancel all suspended orders.
     *
     * @return bool
     */
    public function batch_cancel_suspended($data)
    {
        return $this->getService()->batchCancelSuspended();
    }

    /**
     * Update order config.
     *
     * @return bool
     */
    public function update_config($data)
    {
        $order = $this->_getOrderForAdmin($data);

        if (!isset($data['config']) || !is_array($data['config'])) {
            throw new \FOSSBilling\Exception('Order config not passed');
        }

        $config = $data['config'];

        return $this->getService()->updateOrderConfig($order, $config);
    }

    /**
     * Get order service data.
     *
     * @return array
     */
    public function service($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $order = $this->_getOrderForAdmin($data);
            return $this->getService()->getOrderServiceData($order, $this->getIdentity());
        }

        $this->requireContext(['client']);
        $order = $this->_getOrderForClient($data);
        return $this->getService()->getOrderServiceData($order, $data['id'], $this->getIdentity());
    }

    /**
     * Get paginated order statuses history list.
     *
     * @return array
     */
    public function status_history_get_list($data)
    {
        $order = $this->_getOrderForAdmin($data);
        $data['client_order_id'] = $order->id;
        [$sql, $bindings] = $this->getService()->getOrderStatusSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        return $this->di['pager']->getPaginatedResultSet($sql, $bindings, $per_page);
    }

    /**
     * Add order status history change.
     *
     * @return array
     */
    public function status_history_add($data)
    {
        $order = $this->_getOrderForAdmin($data);
        $required = [
            'status' => 'Order status was not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $notes = $data['notes'] ?? null;
        return $this->getService()->orderStatusAdd($order, $data['status'], $notes);
    }

    /**
     * Remove order status history item.
     *
     * @return bool
     */
    public function status_history_delete($data)
    {
        $required = [
            'id' => 'Order history line id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        return $this->getService()->orderStatusRm($data['id']);
    }

    /**
     * Return order statuses codes with counter.
     *
     * @return array
     */
    public function get_statuses()
    {
        return $this->getService()->counter();
    }

    /**
     * Return available invoice options.
     *
     * @return array
     */
    public function get_invoice_options($data)
    {
        return [
            'issue-invoice' => __trans('Automatically issue renewal invoices'),
            'no-invoice' => __trans('Issue invoices manually'),
        ];
    }

    /**
     * Return order statuses codes with titles.
     *
     * @return array
     */
    public function get_status_pairs($data)
    {
        return [
            \Model_ClientOrder::STATUS_PENDING_SETUP => 'Pending setup',
            \Model_ClientOrder::STATUS_FAILED_SETUP => 'Setup failed',
            \Model_ClientOrder::STATUS_ACTIVE => 'Active',
            \Model_ClientOrder::STATUS_SUSPENDED => 'Suspended',
            \Model_ClientOrder::STATUS_CANCELED => 'Canceled',
        ];
    }

    /**
     * Return order addons list.
     */
    public function addons($data): array
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $model = $this->_getOrderForAdmin($data);
        } else {
            $this->requireContext(['client']);
            $model = $this->_getOrderForClient($data);
        }
        $list = $this->getService()->getOrderAddonsList($model);
        $result = [];
        foreach ($list as $order) {
            $result[] = $this->getService()->toApiArray($order);
        }
        return $result;
    }

    /**
     * List of product pairs offered as an upgrade.
     *
     * @return array
     */
    public function upgradables($data)
    {
        $model = $this->_getOrderForClient($data);
        $product = $this->di['db']->getExistingModelById('Product', $model->product_id);
        $productService = $this->di['mod_service']('product');

        return $productService->getUpgradablePairs($product);
    }

    protected function _getOrderForAdmin($data)
    {
        $required = [
            'id' => 'Order id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        return $this->di['db']->getExistingModelById('ClientOrder', $data['id'], 'Order not found');
    }

    protected function _getOrderForClient($data)
    {
        $required = [
            'id' => 'Order id required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $order = $this->getService()->findForClientById($this->getIdentity(), $data['id']);
        if (!$order instanceof \Model_ClientOrder) {
            throw new \FOSSBilling\Exception('Order not found');
        }

        return $order;
    }

    /**
     * Deletes orders with given IDs.
     *
     * @optional bool $delete_addons - Remove addons also. Default false.
     *
     * @return bool
     */
    public function batch_delete($data)
    {
        $required = [
            'ids' => 'Orders ids not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $delete_addons = isset($data['delete_addons']) && (bool) $data['delete_addons'];
        foreach ($data['ids'] as $id) {
            $this->delete(['id' => $id, 'delete_addons' => $delete_addons]);
        }
        return true;
    }

    public function export_csv($data)
    {
        $data['headers'] ??= [];
        return $this->getService()->exportCSV($data['headers']);
    }
}

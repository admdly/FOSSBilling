<?php
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Invoice;

use FOSSBilling\InformationException;

class Api extends \Api_Abstract
{
    /**
     * Returns paginated list of invoices.
     *
     * @return array
     */
    public function get_list($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $service = $this->getService();
            [$sql, $params] = $service->getSearchQuery($data);
            $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
            $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
            foreach ($pager['list'] as $key => $item) {
                $invoice = $this->di['db']->getExistingModelById('Invoice', $item['id'], 'Invoice not found');
                $pager['list'][$key] = $this->getService()->toApiArray($invoice, true, $this->getIdentity());
            }
            return $pager;
        }

        $this->requireContext(['client']);
        $data['client_id'] = $this->getIdentity()->id;
        $data['approved'] = true;
        [$sql, $params] = $this->getService()->getSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $invoice = $this->di['db']->getExistingModelById('Invoice', $item['id'], 'Invoice not found');
            $pager['list'][$key] = $this->getService()->toApiArray($invoice);
        }

        return $pager;
    }

    /**
     * Get invoice details.
     *
     * @return array
     */
    public function get($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $model = $this->_getInvoice($data);
            return $this->getService()->toApiArray($model, true, $this->getIdentity());
        }

        $this->requireContext(['client', 'guest']);
        $required = [
            'hash' => 'Invoice hash not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->findOne('Invoice', 'hash = :hash', ['hash' => $data['hash']]);
        if (!$model) {
            throw new \FOSSBilling\Exception('Invoice was not found');
        }

        return $this->getService()->toApiArray($model, true, $this->getIdentity());
    }

    /**
     * Sets invoice status to paid. This method differs from invoice update method
     * in a way that it sends notification to Events system, so emails are sent.
     * Also this will try to automatically apply payment if clients balance is
     * available.
     *
     * @optional bool $execute - execute related tasks on invoice items. Default false.
     *
     * @return array
     */
    public function mark_as_paid($data)
    {
        $execute = false;
        if (isset($data['execute']) && $data['execute']) {
            $execute = true;
        }
        $invoice = $this->_getInvoice($data);
        $gateway_id = ['id' => $invoice->gateway_id];

        if (!$gateway_id['id']) {
            throw new InformationException('You must set the payment gateway in the invoice manage tab before marking it as paid.');
        }

        $payGateway = $this->gateway_get($gateway_id);
        $charge = false;

        // Check if the payment type is "Custom Payment", Add the transaction and process it.
        if ($payGateway['code'] == 'Custom' && $payGateway['enabled'] == 1) {
            // create transaction
            $transactionService = $this->di['mod_service']('Invoice', 'Transaction');
            $newtx = $transactionService->create([
                'invoice_id' => $invoice->id,
                'gateway_id' => $invoice->gateway_id,
                'currency' => $invoice->currency,
                'status' => 'received',
                'txn_id' => $data['transactionId'],
            ]);

            try {
                return $transactionService->processTransaction($newtx);
            } catch (\Exception $e) {
                $this->di['logger']->info("Error processing transaction: {$e->getMessage()}.");
            }
        }

        return $this->getService()->markAsPaid($invoice, $charge, $execute);
    }

    /**
     * Prepare invoice for editing and updating.
     * Uses clients details, such as currency assigned to client.
     * If client currency is not defined, sets default currency for client.
     *
     * @optional bool $approve - set true to approve invoice after preparation. Defaults to false
     * @optional int $gateway_id - Selected payment gateway id
     * @optional array $items - list of invoice lines. One line is array of line parameters
     * @optional string $text_1 - text to be displayed before invoice items table
     * @optional string $text_2 - text to be displayed after invoice items table
     *
     * @return int $id - newly generated invoice ID
     */
    public function prepare($data)
    {
        $required = [
            'client_id' => 'Client id is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $client = $this->di['db']->getExistingModelById('Client', $data['client_id'], 'Client not found');

        $invoice = $this->getService()->prepareInvoice($client, $data);

        return $invoice->id;
    }

    /**
     * Approve invoice.
     *
     * @return bool
     */
    public function approve($data)
    {
        $model = $this->_getInvoice($data);

        return $this->getService()->approveInvoice($model, $data);
    }

    /**
     * Add refunds.
     *
     * @optional string $note - note for refund
     *
     * @return bool
     */
    public function refund($data)
    {
        $model = $this->_getInvoice($data);
        $note = $data['note'] ?? null;

        return $this->getService()->refundInvoice($model, $note);
    }

    /**
     * Update invoice details.
     */
    public function update($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $model = $this->_getInvoice($data);
            return $this->getService()->updateInvoice($model, $data);
        }

        $this->requireContext(['client', 'guest']);
        $required = [
            'hash' => 'Invoice hash not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $invoice = $this->di['db']->findOne('Invoice', 'hash = :hash', ['hash' => $data['hash']]);
        if (!$invoice) {
            throw new \FOSSBilling\Exception('Invoice was not found');
        }
        if ($invoice->status == 'paid') {
            throw new \FOSSBilling\InformationException('Paid Invoice cannot be modified');
        }

        $updateParams = [];
        $updateParams['gateway_id'] = $data['gateway_id'] ?? null;

        return $this->getService()->updateInvoice($invoice, $updateParams);
    }

    /**
     * Remove one line from invoice.
     *
     * @return bool
     */
    public function item_delete($data)
    {
        $required = [
            'id' => 'Invoice item id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('InvoiceItem', $data['id'], 'Invoice item was not found');
        $invoiceItemService = $this->di['mod_service']('Invoice', 'InvoiceItem');

        return $invoiceItemService->remove($model);
    }

    /**
     * Delete invoice.
     *
     * @return bool
     */
    public function delete($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $model = $this->_getInvoice($data);
            return $this->getService()->deleteInvoiceByAdmin($model);
        }

        $this->requireContext(['client']);
        $required = [
            'hash' => 'Invoice hash not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->findOne('Invoice', 'hash = :hash', ['hash' => $data['hash']]);
        if (!$model) {
            throw new \FOSSBilling\Exception('Invoice was not found');
        }

        return $this->getService()->deleteInvoiceByClient($model);
    }

    /**
     * Generates new invoice for order.
     *
     * @return string - invoice id or hash
     */
    public function renewal_invoice($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $required = [
                'id' => 'Order id required',
            ];
            $this->di['validator']->checkRequiredParamsForArray($required, $data);
            $model = $this->di['db']->getExistingModelById('ClientOrder', $data['id'], 'Order not found');
            if ($model->price <= 0) {
                throw new InformationException('Order :id is free. No need to generate invoice.', [':id' => $model->id]);
            }
            return $this->getService()->renewInvoice($model, $data);
        }

        $this->requireContext(['client']);
        $required = [
            'order_id' => 'Order id required',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->findOne('ClientOrder', 'client_id = ? and id = ?', [$this->getIdentity()->id, $data['order_id']]);
        if (!$model instanceof \Model_ClientOrder) {
            throw new \FOSSBilling\Exception('Order not found');
        }
        if ($model->price <= 0) {
            throw new \FOSSBilling\InformationException('Order :id is free. No need to generate invoice.', [':id' => $model->id]);
        }
        $service = $this->getService();
        $invoice = $service->generateForOrder($model);
        $service->approveInvoice($invoice, ['id' => $invoice->id, 'use_credits' => true]);
        $this->di['logger']->info('Generated new renewal invoice #%s', $invoice->id);

        return $invoice->hash;
    }

    /**
     * Use credits to pay for invoices
     * if credits are available in clients balance.
     *
     * @optional int $client_id - cover only one client invoices
     *
     * @return bool
     */
    public function batch_pay_with_credits($data)
    {
        return $this->getService()->doBatchPayWithCredits($data);
    }

    /**
     * Cover one invoice with credits.
     *
     * @return bool
     */
    public function pay_with_credits($data)
    {
        $invoice = $this->_getInvoice($data);

        return $this->getService()->payInvoiceWithCredits($invoice);
    }

    /**
     * Generate invoices for expiring orders.
     *
     * @return bool
     */
    public function batch_generate()
    {
        return $this->getService()->generateInvoicesForExpiringOrders();
    }

    /**
     * Action to activate paid invoices lines.
     *
     * @return bool
     */
    public function batch_activate_paid()
    {
        return $this->getService()->doBatchPaidInvoiceActivation();
    }

    /**
     * Send buyer reminders about upcoming payment.
     *
     * @return bool
     */
    public function batch_send_reminders($data)
    {
        return $this->getService()->doBatchRemindersSend();
    }

    /**
     * Calls due events on unpaid and approved invoices.
     *
     * @optional bool $once_per_day - default true. Pass false if you want to execute this action more than once per day
     *
     * @return bool - true if executed, false - if it was already executed
     */
    public function batch_invoke_due_event($data)
    {
        return $this->getService()->doBatchInvokeDueEvent($data);
    }

    /**
     * Send payment reminder notification for client.
     *
     * @return bool
     */
    public function send_reminder($data)
    {
        $invoice = $this->_getInvoice($data);

        return $this->getService()->sendInvoiceReminder($invoice);
    }

    /**
     * Return invoice statuses with counter.
     *
     * @return array
     */
    public function get_statuses($data)
    {
        return $this->getService()->counter();
    }

    /**
     * Process all received transactions.
     *
     * @return bool
     */
    public function transaction_process_all($data)
    {
        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->processReceivedATransactions();
    }

    /**
     * Process selected transaction.
     */
    public function transaction_process($data): bool
    {
        $required = [
            'id' => 'Transaction id is missing',
        ];

        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Transaction', $data['id'], 'Transaction not found');

        $output = null;
        $this->di['events_manager']->fire(['event' => 'onBeforeAdminTransactionProcess', 'params' => ['id' => $model->id]]);

        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->preProcessTransaction($model);
    }

    /**
     * Update transaction details.
     *
     * @optional int $invoice_id - new invoice id
     * @optional string $txn_id - transaction id on payment gateway
     * @optional string $txn_status - transaction status on payment gateway
     * @optional int $gateway_id - Payment gateway ID on FOSSBilling
     * @optional float $amount - Transaction amount
     * @optional string $currency - Currency code. Must be available on FOSSBilling
     * @optional string $type - Currency code. Must be available on FOSSBilling
     * @optional string $status - Transaction status on FOSSBilling
     * @optional bool $validate_ipn - Flag to enable and disable IPN validation for this transaction
     * @optional string $note - Custom note
     *
     * @return bool
     */
    public function transaction_update($data)
    {
        $required = [
            'id' => 'Transaction id is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('Transaction', $data['id'], 'Transaction not found');

        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->update($model, $data);
    }

    /**
     * Create custom transaction.
     *
     * @optional array $get - $_GET data
     * @optional array $post - $_POST data
     * @optional array $server - $_SERVER data
     * @optional array $http_raw_post_data - php://input
     * @optional string $txn_id - transaction id on payment gateway
     * @optional bool $skip_validation - makes params invoice_id and gateway_id optional
     *
     * @return int $id - new transaction id
     */
    public function transaction_create($data)
    {
        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->create($data);
    }

    /**
     * Remove transaction.
     *
     * @return bool
     */
    public function transaction_delete($data)
    {
        $required = [
            'id' => 'Transaction id is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('Transaction', $data['id'], 'Transaction not found');

        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->delete($model);
    }

    /**
     * Get transaction details.
     *
     * @return array
     */
    public function transaction_get($data)
    {
        $required = [
            'id' => 'Transaction id is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('Transaction', $data['id'], 'Transaction not found');

        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->toApiArray($model, true);
    }

    /**
     * Get paginated list of transactions.
     *
     * @optional string $txn_id - search for transactions by transaction id on payment gateway
     *
     * @return array
     */
    public function transaction_get_list($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $transactionService = $this->di['mod_service']('Invoice', 'Transaction');
            [$sql, $params] = $transactionService->getSearchQuery($data);
            $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
            $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
            foreach ($pager['list'] as $key => $item) {
                $transaction = $this->di['db']->getExistingModelById('Transaction', $item['id'], 'Transaction not found');
                $pager['list'][$key] = $transactionService->toApiArray($transaction);
            }
            return $pager;
        }

        $this->requireContext(['client']);
        $data['client_id'] = $this->getIdentity()->id;
        $data['status'] = 'processed';
        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');
        [$sql, $params] = $transactionService->getSearchQuery($data);

        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $transaction = $this->di['db']->getExistingModelById('Transaction', $item['id'], 'Transaction not found');
            $pager['list'][$key] = $transactionService->toApiArray($transaction);
        }

        return $pager;
    }

    /**
     * Return transactions statuses with counter.
     *
     * @return array
     */
    public function transaction_get_statuses($data)
    {
        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->counter();
    }

    /**
     * Get available transaction statuses.
     *
     * @return array
     */
    public function transaction_get_statuses_pairs($data)
    {
        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->getStatusPairs();
    }

    /**
     * Get available transaction statuses.
     *
     * @return array
     */
    public function transaction_statuses($data)
    {
        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->getStatuses();
    }

    /**
     * Get available transaction statuses on gateways.
     *
     * @return array
     */
    public function transaction_gateway_statuses($data)
    {
        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->getGatewayStatuses();
    }

    /**
     * Get available transaction types.
     *
     * @return array
     */
    public function transaction_types($data)
    {
        $transactionService = $this->di['mod_service']('Invoice', 'Transaction');

        return $transactionService->getTypes();
    }

    /**
     * Get available gateways.
     *
     * @return array
     */
    public function gateway_get_list($data)
    {
        $gatewayService = $this->di['mod_service']('Invoice', 'PayGateway');
        [$sql, $params] = $gatewayService->getSearchQuery($data);

        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $gateway = $this->di['db']->getExistingModelById('PayGateway', $item['id'], 'Gateway not found');
            $pager['list'][$key] = $gatewayService->toApiArray($gateway, false, $this->getIdentity());
        }

        return $pager;
    }

    /**
     * Get available gateways pairs.
     *
     * @return array
     */
    public function gateway_get_pairs($data)
    {
        $gatewayService = $this->di['mod_service']('Invoice', 'PayGateway');

        return $gatewayService->getPairs();
    }

    /**
     * Return existing module but not activated.
     *
     * @return array
     */
    public function gateway_get_available(array $data)
    {
        $gatewayService = $this->di['mod_service']('Invoice', 'PayGateway');

        return $gatewayService->getAvailable();
    }

    /**
     * Install available payment gateway.
     *
     * @return true
     */
    public function gateway_install(array $data)
    {
        $required = [
            'code' => 'Payment gateway code is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $code = $data['code'];
        $gatewayService = $this->di['mod_service']('Invoice', 'PayGateway');

        return $gatewayService->install($code);
    }

    /**
     * Get gateway details.
     *
     * @return array
     *
     * @throws \FOSSBilling\Exception
     */
    public function gateway_get($data)
    {
        $required = [
            'id' => 'Gateway id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('PayGateway', $data['id'], 'Gateway not found');

        $gatewayService = $this->di['mod_service']('Invoice', 'PayGateway');

        return $gatewayService->toApiArray($model, true, $this->getIdentity());
    }

    /**
     * Copy gateway from existing one.
     *
     * @throws \FOSSBilling\Exception
     */
    public function gateway_copy($data)
    {
        $required = [
            'id' => 'Gateway id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('PayGateway', $data['id'], 'Gateway not found');
        $gatewayService = $this->di['mod_service']('Invoice', 'PayGateway');

        return $gatewayService->copy($model);
    }

    /**
     * Change gateway settings.
     *
     * @optional string $title - gateway title
     * @optional array $config - gateway config array
     * @optional array $accepted_currencies - list of currencies this gateway supports
     * @optional bool $enabled - flag to enable or disable gateway
     * @optional bool $allow_single - flag to enable or disable single payment option
     * @optional bool $allow_recurrent - flag to enable or disable recurrent payment option
     * @optional bool $test_mode - flag to enable or disable test mode for gateway
     *
     * @return bool
     *
     * @throws \FOSSBilling\Exception
     */
    public function gateway_update($data)
    {
        $required = [
            'id' => 'Gateway id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('PayGateway', $data['id'], 'Gateway not found');
        $gatewayService = $this->di['mod_service']('Invoice', 'PayGateway');

        return $gatewayService->update($model, $data);
    }

    /**
     * Remove payment gateway from system.
     *
     * @return bool
     *
     * @throws \FOSSBilling\Exception
     */
    public function gateway_delete($data)
    {
        $required = [
            'id' => 'Gateway id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('PayGateway', $data['id'], 'Gateway not found');
        $gatewayService = $this->di['mod_service']('Invoice', 'PayGateway');

        return $gatewayService->delete($model);
    }

    /**
     * Get list of subscriptions.
     *
     * @return array
     */
    public function subscription_get_list($data)
    {
        $subscriptionService = $this->di['mod_service']('Invoice', 'Subscription');
        [$sql, $params] = $subscriptionService->getSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $subscription = $this->di['db']->getExistingModelById('Subscription', $item['id'], 'Subscription not found');
            $pager['list'][$key] = $subscriptionService->toApiArray($subscription);
        }

        return $pager;
    }

    /**
     * Add new subscription.
     *
     * @optional string $sid - subscription id on payment gateway
     * @optional string $status - status: active|canceled
     * @optional string $period - example: 1W - every week, 2M - every 2 months
     * @optional string $amount - billed amount
     * @optional string $rel_type - related item type
     * @optional string $rel_id - related item id
     *
     * @return int - id
     *
     * @throws InformationException
     */
    public function subscription_create($data)
    {
        $required = [
            'client_id' => 'Client id not passed',
            'gateway_id' => 'Payment gateway id not passed',
            'currency' => 'Subscription currency not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $client = $this->di['db']->getExistingModelById('Client', $data['client_id'], 'Client not found');
        $payGateway = $this->di['db']->getExistingModelById('PayGateway', $data['gateway_id'], 'Payment gateway not found');

        if ($client->currency != $data['currency']) {
            throw new InformationException('Client currency must match subscription currency. Check if clients currency is defined.');
        }
        $subscriptionService = $this->di['mod_service']('Invoice', 'Subscription');

        return $subscriptionService->create($client, $payGateway, $data);
    }

    /**
     * Update subscription options.
     *
     * @optional int $status - subscription status
     * @optional string $sid - subscription id on payment gateway
     * @optional string $period - subscription period code
     * @optional string $amount - subscription amount
     * @optional string $currency - subscription currency
     *
     * @return bool
     *
     * @throws \FOSSBilling\Exception
     */
    public function subscription_update($data)
    {
        $required = [
            'id' => 'Subscription id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Subscription', $data['id'], 'Subscription not found');
        $subscriptionService = $this->di['mod_service']('Invoice', 'Subscription');

        return $subscriptionService->update($model, $data);
    }

    /**
     * Get subscription details.
     *
     * @return array
     *
     * @throws \FOSSBilling\Exception
     */
    public function subscription_get($data)
    {
        if (!isset($data['id']) && !isset($data['sid'])) {
            $required = [
                'id' => 'Subscription id not passed',
                'sid' => 'Subscription sid not passed',
            ];
            $this->di['validator']->checkRequiredParamsForArray($required, $data);
        }
        $model = null;
        if (isset($data['id'])) {
            $model = $this->di['db']->load('Subscription', $data['id']);
        }

        if (!$model && isset($data['sid'])) {
            $model = $this->di['db']->findOne('Subscription', 'sid = ?', [$data['sid']]);
        }

        if (!$model instanceof \Model_Subscription) {
            throw new \FOSSBilling\Exception('Subscription not found');
        }

        $subscriptionService = $this->di['mod_service']('Invoice', 'Subscription');

        return $subscriptionService->toApiArray($model, true, $this->getIdentity());
    }

    /**
     * Remove subscription.
     *
     * @return bool
     *
     * @throws \FOSSBilling\Exception
     */
    public function subscription_delete($data)
    {
        $required = [
            'id' => 'Subscription id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Subscription', $data['id'], 'Subscription not found');
        $subscriptionService = $this->di['mod_service']('Invoice', 'Subscription');

        return $subscriptionService->delete($model);
    }

    /**
     * Remove tax rule.
     *
     * @return bool
     *
     * @throws \FOSSBilling\Exception
     */
    public function tax_delete($data)
    {
        $required = [
            'id' => 'Tax id is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $model = $this->di['db']->getExistingModelById('Tax', $data['id'], 'Tax rule not found');
        $taxService = $this->di['mod_service']('Invoice', 'Tax');

        return $taxService->delete($model);
    }

    /**
     * Create new tax rule.
     *
     * @return int - new tax id
     */
    public function tax_create($data)
    {
        $required = [
            'name' => 'Tax name is missing',
            'taxrate' => 'Tax rate is missing or is invalid',
        ];

        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $taxService = $this->di['mod_service']('Invoice', 'Tax');

        return $taxService->create($data);
    }

    /**
     * Update tax rule.
     *
     * @return array
     */
    public function tax_get($data)
    {
        $required = [
            'id' => 'Tax id is missing',
        ];

        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        $tax = $this->di['db']->getExistingModelById('Tax', $data['id'], 'Tax rule not found');

        $taxService = $this->di['mod_service']('Invoice', 'Tax');

        return $taxService->toApiArray($tax);
    }

    /**
     * Update tax rule.
     *
     * @return bool
     */
    public function tax_update($data)
    {
        $required = [
            'id' => 'Tax id is missing',
            'taxrate' => 'Tax rate is missing',
            'name' => 'Tax name is missing',
        ];

        $tax = $this->di['db']->getExistingModelById('Tax', $data['id'], 'Tax rule not found');

        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $taxService = $this->di['mod_service']('Invoice', 'Tax');

        return $taxService->update($tax, $data);
    }

    /**
     * Get list of taxes.
     *
     * @return array
     */
    public function tax_get_list($data)
    {
        $taxService = $this->di['mod_service']('Invoice', 'Tax');
        [$sql, $params] = $taxService->getSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();

        return $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
    }

    /**
     * Automatically setup the EU VAT tax rules for you for all EU Member States.
     * This action will delete any existing tax rules and configure the VAT rates
     * for all EU countries.
     *
     * @return bool
     */
    public function tax_setup_eu($data)
    {
        $taxService = $this->di['mod_service']('Invoice', 'Tax');

        return $taxService->setupEUTaxes($data);
    }

    private function _getInvoice($data)
    {
        $required = [
            'id' => 'Invoice id not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->di['db']->getExistingModelById('Invoice', $data['id'], 'Invoice was not found');
    }

    /**
     * Deletes invoices with given IDs.
     *
     * @return bool
     */
    public function batch_delete($data)
    {
        $required = [
            'ids' => 'IDs not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        foreach ($data['ids'] as $id) {
            $this->delete(['id' => $id]);
        }

        return true;
    }

    /**
     * Deletes subscriptions with given IDs.
     *
     * @return bool
     */
    public function batch_delete_subscription($data)
    {
        $required = [
            'ids' => 'IDs not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        foreach ($data['ids'] as $id) {
            $this->subscription_delete(['id' => $id]);
        }

        return true;
    }

    /**
     * Deletes transactions with given IDs.
     *
     * @return bool
     */
    public function batch_delete_transaction($data)
    {
        $required = [
            'ids' => 'IDs not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        foreach ($data['ids'] as $id) {
            $this->transaction_delete(['id' => $id]);
        }

        return true;
    }

    /**
     * Deletes taxes with given IDs.
     *
     * @return bool
     */
    public function batch_delete_tax($data)
    {
        $required = [
            'ids' => 'IDs not passed',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        foreach ($data['ids'] as $id) {
            $this->tax_delete(['id' => $id]);
        }

        return true;
    }

    public function export_csv($data)
    {
        $data['headers'] ??= [];

        return $this->getService()->exportCSV($data['headers']);
    }

    /**
     * Deposit money in advance. Generates new invoice for depositing money.
     * Clients currency must be defined.
     *
     * @return string - invoice hash
     */
    public function funds_invoice($data)
    {
        $required = [
            'amount' => 'Amount is required',
        ];

        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        if (!is_numeric($data['amount'])) {
            throw new \FOSSBilling\InformationException('You need to enter numeric value');
        }

        $service = $this->getService();
        $invoice = $service->generateFundsInvoice($this->getIdentity(), $data['amount']);
        $service->approveInvoice($invoice, ['id' => $invoice->id]);
        $this->di['logger']->info('Generated add funds invoice #%s', $invoice->id);

        return $invoice->hash;
    }

    public function get_tax_rate()
    {
        $service = $this->di['mod_service']('Invoice', 'Tax');

        return $service->getTaxRateForClient($this->getIdentity());
    }

    /**
     * Get list of available payment gateways to pay for invoices.
     *
     * @optional string $format - if format is "pairs" then id=>name values are returned
     *
     * @return array
     */
    public function gateways($data)
    {
        $gatewayService = $this->di['mod_service']('Invoice', 'PayGateway');

        return $gatewayService->getActive($data);
    }

    /**
     * Process invoice for selected gateway. Returned result can be processed
     * to redirect or to show required information. Returned result depends
     * on payment gateway.
     *
     * Tries to detect if invoice can be subscribed and if payment gateway supports subscriptions
     * uses subscription payment.
     *
     * @optional bool $auto_redirect - should payment adapter automatically redirect client or just print pay now button
     *
     * @return array
     *
     * @throws \FOSSBilling\Exception
     */
    public function payment($data)
    {
        if (!isset($data['hash'])) {
            throw new \FOSSBilling\Exception('Invoice hash not passed. Missing param hash', null, 810);
        }

        if (!isset($data['gateway_id'])) {
            throw new \FOSSBilling\Exception('Payment method not found. Missing param gateway_id', null, 811);
        }

        return $this->getService()->processInvoice($data);
    }

    /**
     * Generates PDF for given invoice.
     *
     * @throws \FOSSBilling\Exception
     */
    public function pdf($data)
    {
        $required = [
            'hash' => 'Invoice hash is missing',
        ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);

        return $this->getService()->generatePDF($data['hash'], $this->getIdentity());
    }
}

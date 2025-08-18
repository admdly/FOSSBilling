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

namespace Box\Mod\Billing;

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

    public function getSearchQuery(array $data)
    {
        $sql = 'SELECT m.*
                FROM transaction as m
                LEFT JOIN invoice as i on m.invoice_id = i.id
                WHERE 1 ';

        $id = $data['id'] ?? null;
        $search = $data['search'] ?? null;
        $invoice_hash = $data['invoice_hash'] ?? null;
        $invoice_id = $data['invoice_id'] ?? null;
        $gateway_id = $data['gateway_id'] ?? null;
        $client_id = $data['client_id'] ?? null;
        $status = $data['status'] ?? null;
        $currency = $data['currency'] ?? null;
        $type = $data['type'] ?? null;
        $txn_id = $data['txn_id'] ?? null;

        $date_from = $data['date_from'] ?? null;
        $date_to = $data['date_to'] ?? null;

        $params = [];
        if ($id) {
            $sql .= ' AND m.id = :id';
            $params['id'] = $id;
        }

        if ($status) {
            $sql .= ' AND m.status = :status';
            $params['status'] = $status;
        }

        if ($invoice_hash) {
            $sql .= ' AND i.hash = :hash';
            $params['hash'] = $invoice_hash;
        }

        if ($invoice_id) {
            $sql .= ' AND m.invoice_id = :invoice_id';
            $params['invoice_id'] = $invoice_id;
        }

        if ($gateway_id) {
            $sql .= ' AND m.gateway_id = :gateway_id';
            $params['gateway_id'] = $gateway_id;
        }

        if ($client_id) {
            $sql .= ' AND i.client_id = :client_id';
            $params['client_id'] = $client_id;
        }

        if ($currency) {
            $sql .= ' AND m.currency = :currency';
            $params['currency'] = $currency;
        }

        if ($type) {
            $sql .= ' AND m.type = :type';
            $params['type'] = $type;
        }

        if ($txn_id) {
            $sql .= ' AND m.txn_id = :txn_id';
            $params['txn_id'] = $txn_id;
        }

        if ($date_from) {
            $sql .= ' AND UNIX_TIMESTAMP(m.created_at) >= :date_from';
            $params['date_from'] = strtotime($date_from);
        }

        if ($date_to) {
            $sql .= ' AND UNIX_TIMESTAMP(m.created_at) <= :date_to';
            $params['date_to'] = strtotime($date_to);
        }

        if ($search) {
            $sql .= ' AND m.note LIKE :note OR m.invoice_id LIKE :search_invoice_id OR m.txn_id LIKE :search_txn_id OR m.ipn LIKE :ipn';
            $params['note'] = "%$search%";
            $params['search_invoice_id'] = "%$search%";
            $params['search_txn_id'] = "%$search%";
            $params['ipn'] = "%$search%";
        }

        $sql .= ' ORDER BY m.id DESC';

        return [$sql, $params];
    }

    public function toApiArray(\Model_Transaction $model, $deep = false, $identity = null): array
    {
        $gateway = null;
        if ($model->gateway_id) {
            $gtw = $this->di['db']->load('PayGateway', $model->gateway_id);
            if ($gtw instanceof \Model_PayGateway) {
                $gateway = $gtw->name;
            }
        }

        $result = [
            'id' => $model->id,
            'invoice_id' => $model->invoice_id,
            'txn_id' => $model->txn_id,
            'txn_status' => $model->txn_status,
            'gateway_id' => $model->gateway_id,
            'gateway' => $gateway,
            'amount' => $model->amount,
            'currency' => $model->currency,
            'type' => $model->type,
            'status' => $model->status,
            'ip' => $model->ip,
            'validate_ipn' => $model->validate_ipn,
            'error' => $model->error,
            'error_code' => $model->error_code,
            'note' => $model->note,
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
        ];

        return $result;
    }

    public function create(array $data)
    {
        $this->di['events_manager']->fire(['event' => 'onBeforeTransactionCreate', 'params' => $data]);

        $transaction = $this->di['db']->dispense('Transaction');
        $transaction->client_id = $data['client_id'] ?? null;
        $transaction->invoice_id = $data['invoice_id'] ?? null;
        $transaction->gateway_id = $data['gateway_id'] ?? null;
        $transaction->txn_id = $data['txn_id'] ?? null;
        $transaction->txn_status = $data['txn_status'] ?? null;
        $transaction->s_id = $data['s_id'] ?? null;
        $transaction->s_period = $data['s_period'] ?? null;
        $transaction->amount = $data['amount'] ?? null;
        $transaction->currency = $data['currency'] ?? null;
        $transaction->type = $data['type'] ?? null;
        $transaction->status = $data['status'] ?? 'received';
        $transaction->ip = $this->di['request']->getClientIp();
        $transaction->error = $data['error'] ?? null;
        $transaction->error_code = $data['error_code'] ?? null;
        $transaction->validate_ipn = $data['validate_ipn'] ?? 1;
        $transaction->ipn = $data['ipn'] ?? null;
        $transaction->output = $data['output'] ?? null;
        $transaction->note = $data['note'] ?? null;
        $transaction->created_at = date('Y-m-d H:i:s');
        $transaction->updated_at = date('Y-m-d H:i:s');
        $newId = $this->di['db']->store($transaction);

        $this->di['logger']->info('Created transaction %s', $newId);

        $this->di['events_manager']->fire(['event' => 'onAfterTransactionCreate', 'params' => ['id' => $newId]]);

        return $newId;
    }

    public function update(\Model_Transaction $model, array $data)
    {
        $this->di['events_manager']->fire(['event' => 'onBeforeTransactionUpdate', 'params' => ['id' => $model->id]]);

        $model->invoice_id = $data['invoice_id'] ?? $model->invoice_id;
        $model->txn_id = $data['txn_id'] ?? $model->txn_id;
        $model->txn_status = $data['txn_status'] ?? $model->txn_status;
        $model->gateway_id = $data['gateway_id'] ?? $model->gateway_id;
        $model->amount = $data['amount'] ?? $model->amount;
        $model->currency = $data['currency'] ?? $model->currency;
        $model->type = $data['type'] ?? $model->type;
        $model->note = $data['note'] ?? $model->note;
        $model->status = $data['status'] ?? $model->status;
        $model->error = $data['error'] ?? $model->error;
        $model->error_code = $data['error_code'] ?? $model->error_code;
        $model->validate_ipn = $data['validate_ipn'] ?? $model->validate_ipn;
        $model->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($model);
        $this->di['events_manager']->fire(['event' => 'onAfterTransactionUpdate', 'params' => ['id' => $model->id]]);

        $this->di['logger']->info('Updated transaction #%s', $model->id);

        return true;
    }
}

<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Invoice\Controller;

use FOSSBilling\Controller\AdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Admin extends AdminController
{
    public function fetchNavigation(): array
    {
        return [
            'group' => [
                'index' => 400,
                'location' => 'invoice',
                'label' => __trans('Invoices'),
                'uri' => 'invoice',
                'class' => 'invoices',
            ],
            'subpages' => [
                [
                    'location' => 'invoice',
                    'label' => __trans('Overview'),
                    'uri' => $this->di['url']->adminLink('invoice'),
                    'index' => 100,
                    'class' => '',
                ],
                [
                    'location' => 'invoice',
                    'label' => __trans('Advanced search'),
                    'uri' => $this->di['url']->adminLink('invoice', ['show_filter' => 1]),
                    'index' => 200,
                    'class' => '',
                ],
                [
                    'location' => 'invoice',
                    'label' => __trans('Subscriptions'),
                    'uri' => $this->di['url']->adminLink('invoice/subscriptions'),
                    'index' => 300,
                    'class' => '',
                ],
                [
                    'location' => 'invoice',
                    'label' => __trans('Transactions overview'),
                    'uri' => $this->di['url']->adminLink('invoice/transactions'),
                    'index' => 400,
                    'class' => '',
                ],
                [
                    'location' => 'invoice',
                    'label' => __trans('Transactions search'),
                    'uri' => $this->di['url']->adminLink('invoice/transactions', ['show_filter' => 1]),
                    'index' => 500,
                    'class' => '',
                ],
                [
                    'location' => 'system',
                    'label' => __trans('Tax rules'),
                    'uri' => $this->di['url']->adminLink('invoice/tax'),
                    'index' => 180,
                    'class' => '',
                ],
                [
                    'location' => 'system',
                    'label' => __trans('Payment gateways'),
                    'uri' => $this->di['url']->adminLink('invoice/gateways'),
                    'index' => 160,
                    'class' => '',
                ],
            ],
        ];
    }

    #[Route('/invoice', name: 'invoice_admin_index', methods: ['GET'])]
    public function getIndex(): Response
    {
        return $this->render('mod_invoice_index');
    }

    #[Route('/invoice/subscriptions', name: 'invoice_admin_subscriptions', methods: ['GET'])]
    public function getSubscriptions(): Response
    {
        return $this->render('mod_invoice_subscriptions');
    }

    #[Route('/invoice/transactions', name: 'invoice_admin_transactions', methods: ['GET'])]
    public function getTransactions(): Response
    {
        return $this->render('mod_invoice_transactions');
    }

    #[Route('/invoice/gateways', name: 'invoice_admin_gateways', methods: ['GET'])]
    public function getGateways(): Response
    {
        return $this->render('mod_invoice_gateways');
    }

    #[Route('/invoice/gateway/{id}', name: 'invoice_admin_gateway', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getGateway(int $id): Response
    {
        $api = $this->di['api_admin'];
        $gateway = $api->invoice_gateway_get(['id' => $id]);
        return $this->render('mod_invoice_gateway', ['gateway' => $gateway]);
    }

    #[Route('/invoice/manage/{id}', name: 'invoice_admin_manage', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getInvoice(int $id): Response
    {
        $api = $this->di['api_admin'];
        $invoice = $api->invoice_get(['id' => $id]);
        return $this->render('mod_invoice_invoice', ['invoice' => $invoice]);
    }

    #[Route('/invoice/transaction/{id}', name: 'invoice_admin_transaction', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getTransaction(int $id): Response
    {
        $api = $this->di['api_admin'];
        $tx = $api->invoice_transaction_get(['id' => $id]);
        return $this->render('mod_invoice_transaction', ['transaction' => $tx]);
    }

    #[Route('/invoice/subscription/{id}', name: 'invoice_admin_subscription', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getSubscription(int $id): Response
    {
        $api = $this->di['api_admin'];
        $tx = $api->invoice_subscription_get(['id' => $id]);
        return $this->render('mod_invoice_subscription', ['subscription' => $tx]);
    }

    #[Route('/invoice/tax', name: 'invoice_admin_tax', methods: ['GET'])]
    public function getTaxes(): Response
    {
        return $this->render('mod_invoice_tax');
    }

    #[Route('/invoice/tax/{id}', name: 'invoice_admin_tax_manage', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getTax(int $id): Response
    {
        $api = $this->di['api_admin'];
        $tax = $api->invoice_tax_get(['id' => $id]);
        return $this->render('mod_invoice_taxupdate', ['tax' => $tax]);
    }

    #[Route('/invoice/pdf/{hash}', name: 'invoice_admin_pdf', methods: ['GET'], requirements: ['hash' => '[a-z0-9]+'])]
    public function getPdf(string $hash): Response
    {
        $api = $this->di['api_guest'];
        $data = ['hash' => $hash];
        $invoice = $api->invoice_pdf($data);
        return $this->render('mod_invoice_pdf', ['invoice' => $invoice]);
    }
}

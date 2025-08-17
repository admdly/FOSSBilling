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

use FOSSBilling\Controller\ClientController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Client extends ClientController
{
    #[Route('/invoice', name: 'invoice_client_index', methods: ['GET', 'POST'])]
    public function getInvoices(): Response
    {
        $this->di['is_client_logged'];
        return $this->render('mod_invoice_index');
    }

    #[Route('/invoice/{hash}', name: 'invoice_client_invoice', methods: ['GET', 'POST'], requirements: ['hash' => '[a-z0-9]+'])]
    public function getInvoice(string $hash): Response
    {
        $api = $this->di['api_guest'];
        $data = ['hash' => $hash];
        $invoice = $api->invoice_get($data);
        return $this->render('mod_invoice_invoice', ['invoice' => $invoice]);
    }

    #[Route('/invoice/print/{hash}', name: 'invoice_client_print', methods: ['GET', 'POST'], requirements: ['hash' => '[a-z0-9]+'])]
    public function getInvoicePrint(string $hash): Response
    {
        $api = $this->di['api_guest'];
        $data = ['hash' => $hash];
        $invoice = $api->invoice_get($data);
        return $this->render('mod_invoice_print', ['invoice' => $invoice]);
    }

    #[Route('/invoice/thank-you/{hash}', name: 'invoice_client_thankyou', methods: ['GET', 'POST'], requirements: ['hash' => '[a-z0-9]+'])]
    public function getThankYouPage(string $hash): Response
    {
        $api = $this->di['api_guest'];
        $data = ['hash' => $hash];
        $invoice = $api->invoice_get($data);
        return $this->render('mod_invoice_thankyou', ['invoice' => $invoice]);
    }

    #[Route('/invoice/banklink/{hash}/{id}', name: 'invoice_client_banklink', methods: ['GET'], requirements: ['id' => '\d+', 'hash' => '[a-z0-9]+'])]
    public function getBanklink(Request $request, string $hash, int $id): Response
    {
        $api = $this->di['api_guest'];
        $data = [
            'allow_subscription' => $request->query->getBoolean('allow_subscription', true),
            'hash' => $hash,
            'gateway_id' => $id,
            'auto_redirect' => true,
        ];
        $invoice = $api->invoice_get($data);
        $result = $api->invoice_payment($data);
        return $this->render('mod_invoice_banklink', ['payment' => $result, 'invoice' => $invoice]);
    }

    #[Route('/invoice/pdf/{hash}', name: 'invoice_client_pdf', methods: ['GET'], requirements: ['hash' => '[a-z0-9]+'])]
    public function getPdf(string $hash): Response
    {
        $api = $this->di['api_guest'];
        $data = ['hash' => $hash];
        $invoice = $api->invoice_pdf($data);
        return $this->render('mod_invoice_pdf', ['invoice' => $invoice]);
    }
}

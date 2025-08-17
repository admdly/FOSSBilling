<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Currency\Controller;

use FOSSBilling\Controller\AdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Admin extends AdminController
{
    #[Route('/currency/manage/{code}', name: 'currency_admin_manage', methods: ['GET'], requirements: ['code' => '[a-zA-Z]+'])]
    public function getManage(string $code): Response
    {
        $guest_api = $this->di['api_guest'];
        $currency = $guest_api->currency_get(['code' => $code]);

        return $this->render('mod_currency_manage', ['currency' => $currency]);
    }
}

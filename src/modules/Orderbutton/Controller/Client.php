<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Orderbutton\Controller;

use FOSSBilling\Controller\ClientController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Client extends ClientController
{
    #[Route('/orderbutton', name: 'orderbutton_client_index', methods: ['GET'])]
    public function getIndex(): Response
    {
        return $this->render('mod_orderbutton_index');
    }

    #[Route('/orderbutton/js', name: 'orderbutton_client_js', methods: ['GET'])]
    public function getJs(): Response
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/javascript');
        return $this->render('mod_orderbutton_js', [], $response);
    }
}

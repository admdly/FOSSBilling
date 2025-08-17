<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Email\Controller;

use FOSSBilling\Controller\ClientController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Client extends ClientController
{
    #[Route('/email', name: 'email_client_index', methods: ['GET'])]
    public function getEmails(): Response
    {
        $this->di['is_client_logged'];
        return $this->render('mod_email_index');
    }

    #[Route('/email/{id}', name: 'email_client_email', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getEmail(int $id): Response
    {
        $this->di['is_client_logged'];
        $api = $this->di['api_client'];
        $data = ['id' => $id];
        $email = $api->email_get($data);

        return $this->render('mod_email_email', ['email' => $email]);
    }
}

<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Massmailer\Controller;

use FOSSBilling\Controller\AdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Admin extends AdminController
{
    public function fetchNavigation(): array
    {
        return [
            'subpages' => [
                [
                    'location' => 'extensions',
                    'index' => 4000,
                    'label' => __trans('Mass mailer'),
                    'uri' => $this->di['url']->adminLink('massmailer'),
                    'class' => '',
                ],
            ],
        ];
    }

    #[Route('/massmailer', name: 'massmailer_admin_index', methods: ['GET'])]
    public function getIndex(): Response
    {
        return $this->render('mod_massmailer_index');
    }

    #[Route('/massmailer/message/{id}', name: 'massmailer_admin_message', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getEdit(int $id): Response
    {
        $api = $this->di['api_admin'];
        $model = $api->massmailer_get(['id' => $id]);
        return $this->render('mod_massmailer_message', ['msg' => $model]);
    }
}

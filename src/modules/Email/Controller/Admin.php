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
                    'location' => 'activity',
                    'index' => 200,
                    'label' => __trans('Email history'),
                    'uri' => $this->di['url']->adminLink('email/history'),
                    'class' => '',
                ],
            ],
        ];
    }

    #[Route('/email/history', name: 'email_admin_history', methods: ['GET'])]
    public function getHistory(): Response
    {
        return $this->render('mod_email_history');
    }

    #[Route('/email/templates', name: 'email_admin_templates', methods: ['GET'])]
    public function getIndex(): Response
    {
        // This method was missing in the original controller, but the route existed.
        // Assuming it should render a template listing all email templates.
        // The template name is an assumption based on the route.
        return $this->render('mod_email_templates');
    }

    #[Route('/email/template/{id}', name: 'email_admin_template', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getTemplate(int $id): Response
    {
        $api = $this->di['api_admin'];
        $template = $api->email_template_get(['id' => $id]);

        return $this->render('mod_email_template', ['template' => $template]);
    }

    #[Route('/email/{id}', name: 'email_admin_email', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getEmail(int $id): Response
    {
        $api = $this->di['api_admin'];
        $email = $api->email_email_get(['id' => $id]);

        return $this->render('mod_email_details', ['email' => $email]);
    }
}

<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Client\Controller;

use FOSSBilling\Controller\ClientController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Client extends ClientController
{
    #[Route('/client', name: 'client_index', methods: ['GET'])]
    public function getIndex(): Response
    {
        $this->di['is_client_logged'];
        return $this->render('mod_client_index');
    }

    #[Route('/client/logout', name: 'client_logout', methods: ['GET'])]
    public function getLogout(): Response
    {
        $api = $this->di['api_client'];
        $api->profile_logout();
        return new RedirectResponse($this->di['url']->link('/'));
    }

    #[Route('/client/confirm-email/{hash}', name: 'client_confirm_email', methods: ['GET'], requirements: ['hash' => '[a-z0-9]+'])]
    public function getEmailConfirmation(string $hash): Response
    {
        $service = $this->di['mod_service']('client');
        $service->approveClientEmailByHash($hash);
        $systemService = $this->di['mod_service']('System');
        $systemService->setPendingMessage(__trans('Email address was confirmed'));
        return new RedirectResponse($this->di['url']->link('/'));
    }

    #[Route('/client/reset-password-confirm/{hash}', name: 'client_reset_password_confirm', methods: ['GET'], requirements: ['hash' => '[a-z0-9]+'])]
    public function getResetPasswordConfirm(string $hash): Response
    {
        $service = $this->di['mod_service']('client');
        $this->di['events_manager']->fire(['event' => 'onBeforePasswordResetClient']);
        $data = ['hash' => $hash];

        if ($service->password_reset_valid($data)) {
            return $this->render('mod_client_set_new_password');
        } else {
            return new RedirectResponse($this->di['url']->link('/'));
        }
    }

    #[Route('/client/{page}', name: 'client_page', methods: ['GET'], requirements: ['page' => '[a-z0-9-]+'])]
    public function getPage(string $page): Response
    {
        $this->di['is_client_logged'];
        $template = 'mod_client_' . $page;
        return $this->render($template);
    }
}

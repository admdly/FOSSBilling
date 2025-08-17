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

use FOSSBilling\Controller\AdminController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Admin extends AdminController
{
    public function fetchNavigation(): array
    {
        return [
            'group' => [
                'index' => 200,
                'location' => 'client',
                'label' => __trans('Clients'),
                'uri' => $this->di['url']->adminLink('client'),
                'class' => 'contacts',
            ],
            'subpages' => [
                [
                    'location' => 'client',
                    'label' => __trans('Overview'),
                    'uri' => $this->di['url']->adminLink('client'),
                    'index' => 100,
                    'class' => '',
                ],
                [
                    'location' => 'client',
                    'label' => __trans('Advanced search'),
                    'uri' => $this->di['url']->adminLink('client', ['show_filter' => 1]),
                    'index' => 200,
                    'class' => '',
                ],
                [
                    'location' => 'activity',
                    'index' => 900,
                    'label' => __trans('Client login history'),
                    'uri' => $this->di['url']->adminLink('client/logins'),
                    'class' => '',
                ],
            ],
        ];
    }

    #[Route('/client', name: 'client_admin_index', methods: ['GET'])]
    #[Route('/client/', name: 'client_admin_index_slash', methods: ['GET'])]
    public function getIndex(): Response
    {
        return $this->render('mod_client_index');
    }

    #[Route('/client/login/{id}', name: 'client_admin_login', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getLogin(Request $request, int $id): Response
    {
        $api = $this->di['api_admin'];
        $api->client_login(['id' => $id]);

        $redirect_to = '/';
        $r = $request->query->get('r');
        if ($r) {
            $redirect_to = '/' . trim($r, '/');
        }

        return new RedirectResponse($this->di['tools']->url($redirect_to), 301);
    }

    #[Route('/client/manage/{id}', name: 'client_admin_manage', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getManage(int $id): Response
    {
        $api = $this->di['api_admin'];
        $client = $api->client_get(['id' => $id]);

        return $this->render('mod_client_manage', ['client' => $client]);
    }

    #[Route('/client/group/{id}', name: 'client_admin_group', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getGroup(int $id): Response
    {
        $api = $this->di['api_admin'];
        $model = $api->client_group_get(['id' => $id]);

        return $this->render('mod_client_group', ['group' => $model]);
    }

    #[Route('/client/logins', name: 'client_admin_history', methods: ['GET'])]
    public function getHistory(): Response
    {
        return $this->render('mod_client_login_history');
    }
}

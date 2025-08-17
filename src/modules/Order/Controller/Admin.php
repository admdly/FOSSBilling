<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Order\Controller;

use FOSSBilling\Controller\AdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Admin extends AdminController
{
    public function fetchNavigation(): array
    {
        return [
            'group' => [
                'index' => 300,
                'location' => 'order',
                'label' => __trans('Orders'),
                'uri' => $this->di['url']->adminLink('order'),
                'class' => 'orders',
            ],
            'subpages' => [
                [
                    'location' => 'order',
                    'index' => 100,
                    'label' => __trans('Overview'),
                    'uri' => $this->di['url']->adminLink('order'),
                    'class' => '',
                ],
                [
                    'location' => 'order',
                    'index' => 200,
                    'label' => __trans('Advanced search'),
                    'uri' => $this->di['url']->adminLink('order', ['show_filter' => 1]),
                    'class' => '',
                ],
            ],
        ];
    }

    #[Route('/order', name: 'order_admin_index', methods: ['GET'])]
    public function getIndex(): Response
    {
        return $this->render('mod_order_index');
    }

    #[Route('/order/new', name: 'order_admin_new', methods: ['POST'])]
    public function getNew(Request $request): Response
    {
        $api = $this->di['api_admin'];
        $product = $api->product_get(['id' => $request->request->get('product_id')]);
        $client = $api->client_get(['id' => $request->request->get('client_id')]);
        return $this->render('mod_order_new', ['product' => $product, 'client' => $client]);
    }

    #[Route('/order/manage/{id}', name: 'order_admin_manage', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getOrder(int $id): Response
    {
        $api = $this->di['api_admin'];
        $data = ['id' => $id];
        $order = $api->order_get($data);
        $set = ['order' => $order];

        if (isset($order['plugin']) && !empty($order['plugin'])) {
            $set['plugin'] = 'plugin_' . $order['plugin'] . '_manage.html.twig';
        }

        return $this->render('mod_order_manage', $set);
    }
}

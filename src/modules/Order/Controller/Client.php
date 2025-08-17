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

use FOSSBilling\Controller\ClientController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Client extends ClientController
{
    #[Route('/order', name: 'order_client_products', methods: ['GET'])]
    public function getProducts(): Response
    {
        return $this->render('mod_order_index');
    }

    #[Route('/order/service', name: 'order_client_orders', methods: ['GET'])]
    public function getOrders(): Response
    {
        $this->di['is_client_logged'];
        return $this->render('mod_order_list');
    }

    #[Route('/order/service/manage/{id}', name: 'order_client_manage', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getOrder(int $id): Response
    {
        $this->di['is_client_logged'];
        $api = $this->di['api_client'];
        $data = ['id' => $id];
        $order = $api->order_get($data);
        return $this->render('mod_order_manage', ['order' => $order]);
    }

    #[Route('/order/{slug}', name: 'order_client_configure_product_by_slug', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function getConfigureProductBySlug(string $slug): Response
    {
        $api = $this->di['api_guest'];
        $product = $api->product_get(['slug' => $slug]);
        $tpl = 'mod_service' . $product['type'] . '_order';
        if ($api->system_template_exists(['file' => $tpl . '.html.twig'])) {
            return $this->render($tpl, ['product' => $product]);
        }
        return $this->render('mod_order_product', ['product' => $product]);
    }

    #[Route('/order/{id}', name: 'order_client_configure_product', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getConfigureProduct(int $id): Response
    {
        $api = $this->di['api_guest'];
        $product = $api->product_get(['id' => $id]);
        $tpl = 'mod_service' . $product['type'] . '_order';
        if ($api->system_template_exists(['file' => $tpl . '.html.twig'])) {
            return $this->render($tpl, ['product' => $product]);
        }
        return $this->render('mod_order_product', ['product' => $product]);
    }
}

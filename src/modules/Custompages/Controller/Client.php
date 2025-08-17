<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Custompages\Controller;

use FOSSBilling\Controller\ClientController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Client extends ClientController
{
    #[Route('/custompages/{slug}', name: 'custompages_client_page', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function getPage(string $slug): Response
    {
        $service = new \Box\Mod\Custompages\Service();
        $service->setDi($this->di);
        $page = $service->getPage($slug, 'slug');

        if (isset($page['id'])) {
            return $this->render('mod_custompages_content', ['page' => $page]);
        } else {
            return new RedirectResponse($this->di['url']->get(''));
        }
    }
}

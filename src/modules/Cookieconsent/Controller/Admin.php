<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Cookieconsent\Controller;

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
                    'label' => __trans('Cookie consent'),
                    'index' => 2000,
                    'uri' => $this->di['url']->adminLink('cookieconsent'),
                    'class' => '',
                ],
            ],
        ];
    }

    #[Route('/cookieconsent', name: 'cookieconsent_admin_index', methods: ['GET'])]
    public function getIndex(): Response
    {
        return $this->render('mod_cookieconsent_index');
    }
}

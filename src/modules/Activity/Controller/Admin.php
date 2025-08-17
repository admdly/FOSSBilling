<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Activity\Controller;

use FOSSBilling\Controller\AdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Admin extends AdminController
{
    public function fetchNavigation(): array
    {
        return [
            'group' => [
                'index' => 700,
                'location' => 'activity',
                'label' => __trans('Activity'),
                'class' => 'graphs',
            ],
            'subpages' => [
                [
                    'location' => 'activity',
                    'label' => __trans('Event history'),
                    'index' => 100,
                    'uri' => $this->di['url']->adminLink('activity'),
                    'class' => '',
                ],
            ],
        ];
    }

    #[Route('/activity', name: 'activity_admin_index', methods: ['GET'])]
    public function getIndex(): Response
    {
        return $this->render('mod_activity_index');
    }
}

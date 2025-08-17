<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Extension\Controller;

use FOSSBilling\Controller\AdminController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Admin extends AdminController
{
    public function fetchNavigation(): array
    {
        return [
            'group' => [
                'location' => 'extensions',
                'index' => 1000,
                'label' => __trans('Extensions'),
                'class' => 'iPlugin',
            ],
            'subpages' => [
                [
                    'location' => 'extensions',
                    'label' => __trans('Overview'),
                    'uri' => $this->di['url']->adminLink('extension'),
                    'index' => 100,
                    'class' => '',
                ],
                [
                    'location' => 'extensions',
                    'label' => __trans('Languages'),
                    'index' => 200,
                    'uri' => $this->di['url']->adminLink('extension/languages'),
                    'class' => '',
                ],
            ],
        ];
    }

    #[Route('/extension', name: 'extension_admin_index', methods: ['GET'])]
    public function getIndex(): Response
    {
        return $this->render('mod_extension_index');
    }

    #[Route('/extension/languages', name: 'extension_admin_languages', methods: ['GET'])]
    public function getLangs(): Response
    {
        return $this->render('mod_extension_languages');
    }

    #[Route('/extension/settings/{mod}', name: 'extension_admin_settings', methods: ['GET'], requirements: ['mod' => '[a-z0-9-]+'])]
    public function getSettings(string $mod): Response
    {
        $extensionService = $this->di['mod_service']('Extension');
        // The hasManagePermission method redirects if the user does not have permission, so we don't need to handle the return value.
        $extensionService->hasManagePermission($mod);

        $file = 'mod_' . $mod . '_settings';
        return $this->render($file);
    }
}

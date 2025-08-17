<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\News\Controller;

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
                    'location' => 'support',
                    'index' => 900,
                    'label' => __trans('Announcements'),
                    'uri' => $this->di['url']->adminLink('news'),
                    'class' => '',
                ],
            ],
        ];
    }

    #[Route('/news', name: 'news_admin_index', methods: ['GET'])]
    public function getIndex(): Response
    {
        return $this->render('mod_news_index');
    }

    #[Route('/news/post/{id}', name: 'news_admin_post', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getPost(int $id): Response
    {
        $api = $this->di['api_admin'];
        $post = $api->news_get(['id' => $id]);
        return $this->render('mod_news_post', ['post' => $post]);
    }
}

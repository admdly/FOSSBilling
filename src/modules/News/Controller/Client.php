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

use FOSSBilling\Controller\ClientController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Client extends ClientController
{
    #[Route('/news', name: 'news_client_index', methods: ['GET'])]
    public function getNews(): Response
    {
        return $this->render('mod_news_index');
    }

    #[Route('/news/{slug}', name: 'news_client_item', methods: ['GET'], requirements: ['slug' => '[a-z0-9-]+'])]
    public function getNewsItem(string $slug): Response
    {
        $post = $this->di['api_guest']->news_get(['slug' => $slug]);
        return $this->render('mod_news_post', ['post' => $post]);
    }
}

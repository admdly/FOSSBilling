<?php

/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Embed\Controller;

use FOSSBilling\Controller\ClientController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class Client extends ClientController
{
    #[Route('/embed/{what}', name: 'embed_client_object', methods: ['GET'], requirements: ['what' => '[a-z0-9-]+'])]
    public function getObject(string $what): Response
    {
        $tpl = 'mod_embed_' . $what;
        return $this->render($tpl);
    }
}

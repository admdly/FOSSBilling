<?php

declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Seo;

class Api extends \Api_Abstract
{
    /**
     * Returns SEO information. When the pings were was last sent.
     *
     * @admin
     *
     * @return array
     */
    public function info($data)
    {
        $this->assertAdmin();

        return $this->getService()->getInfo();
    }

    /**
     * Ping every search engine to let them know that the sitemap has been updated.
     *
     * @admin
     *
     * @return bool
     */
    public function ping_all()
    {
        $this->assertAdmin();

        $extensionService = $this->di['mod_service']('extension');
        $config = $extensionService->getConfig('mod_seo');

        return $this->getService()->pingSitemap($config, true);
    }
}

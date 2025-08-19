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

namespace Box\Mod\Cookieconsent;

use FOSSBilling\Exception;

class Api extends \Api_Abstract
{
    /**
     * Retrieve message that should be displayed in cookie consent notification bar.
     *
     * @todo Guest context only.
     *
     * @return string The message to display in the notification bar.
     *
     * @throws Exception If the message cannot be retrieved.
     */
    public function message(): string
    {
        return $this->getService()->getMessage();
    }
}

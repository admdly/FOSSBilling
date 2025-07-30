<?php

declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Serviceapikey;

class Api extends \Api_Abstract
{
    /**
     * Update an API key. Can be used to change the config, but not to reset / regenerate the key itself.
     *
     * @admin
     *
     * @param array $data - An associative array
     *                    - int 'order_id' (required) The order ID of the API key to reset.
     *                    - array 'config' (optional) The new configuration for the API key. Overrides the previous one, so you must send the complete config rather than only the parameters you want to change.
     */
    public function update($data): bool
    {
        $this->assertAdmin();
        return $this->getService()->updateApiKey($data);
    }

    /**
     * Used to reset / regenerate an API key. Useful in the event one is accidentally leaked.
     *
     * @admin, @client
     *
     * @param array $data - An associative array containing either the key or ID of whatever API key you want to reset.
     *                    - string 'key' The API key to reset.
     *                    - int 'order_id' The order ID of the API key to reset.
     */
    public function reset($data): bool
    {
        $this->assertAuthenticated();
        return $this->getService()->resetApiKey($data);
    }

    /**
     * Checks if an API key is valid or not.
     *
     * @guest
     *
     * @param array $data - 'key' What API key to check.
     */
    public function check($data)
    {
        return $this->getService()->isValid($data);
    }

    /**
     * Gets the information tied to an API key such as its validity and any custom parameters tied to it.
     *
     * @guest
     *
     * @param array $data - 'key' What API key to check & get custom parameters for
     */
    public function get_info($data)
    {
        return $this->getService()->getInfo($data);
    }
}

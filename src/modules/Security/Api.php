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

namespace Box\Mod\Security;

use FOSSBilling\InformationException;

class Api extends \Api_Abstract
{
    /**
     * Lookup information about an IP address.
     *
     * @admin
     */
    public function ip_lookup(array $data): array
    {
        $this->assertAdmin();
        if (!isset($data['ip'])) {
            throw new InformationException('You must specify an IP address to lookup.');
        }
        return $this->getService()->lookupIP($data['ip']);
    }

    /**
     * List all security checks.
     *
     * @admin
     */
    public function list_checks(array $data): array
    {
        $this->assertAdmin();
        $result = [];
        $checkInterfaces = $this->getService()->getAllChecks();
        foreach ($checkInterfaces as $id => $interface) {
            $result[] = [
                'id' => $id,
                'name' => $interface->getName(),
                'description' => $interface->getDescription(),
            ];
        }
        return $result;
    }

    /**
     * Run a specific security check by ID.
     *
     * @admin
     */
    public function run_check(array $data): array
    {
        $this->assertAdmin();
        if (!isset($data['id'])) {
            throw new InformationException('You must specify a check ID to run.');
        }
        return $this->getService()->runCheck($data['id']);
    }

    /**
     * Run all security checks.
     *
     * @admin
     */
    public function run_checks(array $data): array
    {
        $this->assertAdmin();
        return $this->getService()->runAllChecks();
    }
}

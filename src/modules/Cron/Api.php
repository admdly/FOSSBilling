<?php

declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling.
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Cron;

use FOSSBilling\InformationException;

class Api extends \Api_Abstract
{
    /**
     * Returns cron job information, including: when last executed and where
     * cron job file is located.
     *
     * @param array $data
     *
     * @return array
     */
    public function info($data): array
    {
        return $this->getService()->getCronInfo();
    }

    /**
     * Run cron.
     *
     * @param array $data
     *
     * @return bool
     */
    public function run($data = []): bool
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            return $this->getService()->runCrons();
        }

        // Guest context
        $this->requireContext(['guest']);
        $config = $this->getMod()->getConfig();
        $allowGuest = $config['guest_cron'] ?? false;
        if (!$allowGuest) {
            throw new InformationException('You do not have permission to perform this action', [], 403);
        }

        $t1 = new \DateTime($this->getService()->getLastExecutionTime());
        $t2 = new \DateTime('-1min');

        // Ensure this can't be used to run cron more than 1 time every minute.
        if ($t1 >= $t2) {
            return false;
        }

        return $this->getService()->runCrons();
    }
}

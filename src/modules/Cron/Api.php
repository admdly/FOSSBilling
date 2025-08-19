<?php
/**
 * Copyright 2022-2025 FOSSBilling
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
     * Returns cron job information. When it was last executed, where cron job
     * file is located.
     *
     * @return array
     */
    public function info($data)
    {
        return $this->getService()->getCronInfo();
    }

    /**
     * Run cron.
     *
     * @return bool
     */
    public function run($data = [])
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            return $this->getService()->runCrons();
        }

        // Guest context.
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

    /**
     * Get cron settings.
     */
    public function settings(): array
    {
        $this->requireContext(['guest']);

        return $this->getMod()->getConfig();
    }

    /**
     * Tells if cron is late.
     */
    public function is_late(): bool
    {
        $this->requireContext(['guest']);

        return $this->getService()->isLate();
    }
}

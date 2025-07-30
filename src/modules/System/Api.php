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

namespace Box\Mod\System;

use FOSSBilling\Config;
use FOSSBilling\i18n;

class Api extends \Api_Abstract
{
    /**
     * Get all defined system params.
     *
     * @admin
     *
     * @return array
     */
    public function get_params($data)
    {
        $this->assertAdmin();
        return $this->getService()->getParams($data);
    }

    /**
     * Update parameters array with new values.
     *
     * @admin
     *
     * @return bool
     */
    public function update_params($data)
    {
        $this->assertAdmin();
        return $this->getService()->updateParams($data);
    }

    /**
     * System messages about working environment.
     *
     * @admin
     *
     * @return array
     */
    public function messages($data)
    {
        $this->assertAdmin();
        $type = $data['type'] ?? 'info';
        return $this->getService()->getMessages($type);
    }

    /**
     * Get Central Alerts System messages sent for this installation.
     *
     * @admin
     *
     * @return array
     */
    public function cas_messages()
    {
        $this->assertAdmin();
        return $this->getService()->getCasMessages();
    }

    /**
     * Check if passed file name template exists for admin area.
     *
     * @admin
     *
     * @return bool
     */
    public function admin_template_exists($data)
    {
        $this->assertAdmin();
        if (!isset($data['file'])) {
            return false;
        }
        return $this->getService()->templateExists($data['file'], $this->getIdentity());
    }

    /**
     * Parse string like FOSSBilling template.
     *
     * @admin
     *
     * @return string
     */
    public function string_render($data)
    {
        $this->assertAdmin();
        if (!isset($data['_tpl'])) {
            error_log('_tpl parameter not passed');
            return '';
        }
        $tpl = $data['_tpl'];
        $try_render = $data['_try'] ?? false;
        $vars = $data;
        unset($vars['_tpl'], $vars['_try']);
        return $this->getService()->renderString($tpl, $try_render, $vars);
    }

    /**
     * Returns system environment information.
     *
     * @admin
     *
     * @return array
     */
    public function env($data)
    {
        $this->assertAdmin();
        $ip = $data['ip'] ?? null;
        return $this->getService()->getEnv($ip);
    }

    /**
     * Method to check if staff member has permission to access module.
     *
     * @admin
     *
     * @return bool
     */
    public function is_allowed($data)
    {
        $this->assertAdmin();
        $required = [ 'mod' => 'mod key is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $f = $data['f'] ?? null;
        $service = $this->di['mod_service']('Staff');
        return $service->hasPermission($this->getIdentity(), $data['mod'], $f);
    }

    /**
     * Clear system cache.
     *
     * @admin
     *
     * @return bool
     */
    public function clear_cache()
    {
        $this->assertAdmin();
        $this->di['mod_service']('Staff')->checkPermissionsAndThrowException('system', 'invalidate_cache');
        return $this->getService()->clearCache();
    }

    /**
     * Used to check if there's an update available.
     *
     * @admin
     */
    public function update_available(): bool
    {
        $this->assertAdmin();
        $updater = $this->di['updater'];
        return $updater->isUpdateAvailable();
    }

    /**
     * Returns an array containing the update info.
     *
     * @admin
     */
    public function update_info(): array
    {
        $this->assertAdmin();
        $updater = $this->di['updater'];
        return $updater->getLatestVersionInfo();
    }

    /**
     * Forces the system to clear out the update cache and re-fetch the latest info.
     *
     * @admin
     */
    public function recheck_update(): bool
    {
        $this->assertAdmin();
        $updater = $this->di['updater'];
        $updater->getLatestVersionInfo(null, true);
        return true;
    }

    /**
     * Update FOSSBilling core.
     *
     * @admin
     * @return bool
     */
    public function update_core($data)
    {
        $this->assertAdmin();
        $updater = $this->di['updater'];
        if ($updater->getUpdateBranch() !== 'preview' && !$updater->isUpdateAvailable()) {
            throw new \FOSSBilling\InformationException('You have the latest version of FOSSBilling. You do not need to update.');
        }
        $this->di['mod_service']('Staff')->checkPermissionsAndThrowException('system', 'system_update');
        $new_version = $updater->getLatestVersion();
        $this->di['events_manager']->fire(['event' => 'onBeforeAdminUpdateCore']);
        $updater->performUpdate();
        $this->di['events_manager']->fire(['event' => 'onAfterAdminUpdateCore']);
        $this->di['logger']->info('Updated FOSSBilling from %s to %s', \FOSSBilling\Version::VERSION, $new_version);
        return true;
    }

    /**
     * Update FOSSBilling config.
     *
     * @admin
     */
    public function manual_update(): bool
    {
        $this->assertAdmin();
        $this->di['mod_service']('Staff')->checkPermissionsAndThrowException('system', 'system_update');
        $updater = $this->di['updater'];
        $this->di['events_manager']->fire(['event' => 'onBeforeAdminManualUpdate']);
        $updater->performManualUpdate();
        $this->di['events_manager']->fire(['event' => 'onAfterAdminManualUpdate']);
        $this->di['logger']->info('Updated FOSSBilling - applied patches and updated configuration file.');
        return true;
    }

    /**
     * Checks if the database is behind on patches.
     *
     * @admin
     */
    public function is_behind_on_patches(): bool
    {
        $this->assertAdmin();
        $updater = $this->di['updater'];
        return $updater->isBehindOnDBPatches();
    }

    /**
     * Returns the unique instance ID for this FOSSBilling installation.
     *
     * @admin
     */
    public function instance_id(): string
    {
        $this->assertAdmin();
        return INSTANCE_ID;
    }

    /**
     * Returns if error reporting is enabled or not on this FOSSBilling instance.
     *
     * @admin
     */
    public function error_reporting_enabled(): bool
    {
        $this->assertAdmin();
        return (bool) Config::getProperty('debug_and_monitoring.report_errors', false);
    }

    /**
     * Toggles error reporting on this FOSSBilling instance.
     *
     * @admin
     */
    public function toggle_error_reporting(): bool
    {
        $this->assertAdmin();
        $current = Config::getProperty('debug_and_monitoring.report_errors', false);
        Config::setProperty('debug_and_monitoring.report_errors', !$current);
        return true;
    }

    /**
     * Returns the last FOSSBilling version number that changed error reporting behavior.
     *
     * @admin
     */
    public function last_error_reporting_change(): string
    {
        $this->assertAdmin();
        return \FOSSBilling\SentryHelper::last_change;
    }

    /**
     * Returns available HTTP interface IPs.
     *
     * @admin
     */
    public function get_interface_ips(): array
    {
        $this->assertAdmin();
        return \FOSSBilling\Tools::listHttpInterfaces();
    }

    /**
     * Set HTTP interface IPs.
     *
     * @admin
     */
    public function set_interface_ip($data): bool
    {
        $this->assertAdmin();
        $this->di['mod_service']('Staff')->checkPermissionsAndThrowException('system', 'manage_network_interface');
        $config = Config::getConfig();
        if (isset($data['interface'])) {
            $config['interface_ip'] = $data['interface'];
        }
        if (isset($data['custom_interface'])) {
            $config['custom_interface_ip'] = $data['custom_interface'];
        }
        Config::setConfig($config);
        return true;
    }

    /**
     * Get FOSSBilling version.
     *
     * @guest
     * @return string
     */
    public function version()
    {
        $hideVersionGuest = $this->getService()->getParamValue('hide_version_public');
        $identity = $this->getIdentity();
        if ($this->isAdmin($identity) || !$hideVersionGuest) {
            return $this->getService()->getVersion();
        } else {
            return '';
        }
    }

    /**
     * Returns company information.
     *
     * @guest
     * @return array
     */
    public function company()
    {
        $companyInfo = $this->getService()->getCompany();
        $auth = $this->di['auth'];
        $hideExtraCompanyInfoFromGuest = $this->getService()->getParamValue('hide_company_public');
        if (!$auth->isAdminLoggedIn() && !$auth->isClientLoggedIn() && $hideExtraCompanyInfoFromGuest) {
            unset($companyInfo['vat_number'], $companyInfo['email'], $companyInfo['tel'], $companyInfo['account_number'], $companyInfo['number'], $companyInfo['address_1'], $companyInfo['address_2'], $companyInfo['address_3'], $companyInfo['bank_name'], $companyInfo['bic']);
        }
        return $companyInfo;
    }

    /**
     * Returns world wide phone codes.
     *
     * @guest
     *
     * @optional $country - if passed country code the result will be phone code only
     * @return array
     */
    public function phone_codes($data)
    {
        return $this->getService()->getPhoneCodes($data);
    }

    /**
     * Returns USA states list.
     *
     * @guest
     *
     * @return array
     */
    public function states()
    {
        return $this->getService()->getStates();
    }

    /**
     * Returns list of european union countries.
     *
     * @guest
     *
     * @return array
     */
    public function countries_eunion()
    {
        return $this->getService()->getEuCountries();
    }

    /**
     * Returns list of world countries.
     *
     * @guest
     *
     * @return array
     */
    public function countries()
    {
        return $this->getService()->getCountries();
    }

    /**
     * Returns system parameter by key.
     *
     * @guest
     *
     * @return string
     */
    public function param($data)
    {
        $required = [ 'key' => 'Parameter key is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        return $this->getService()->getPublicParamValue($data['key']);
    }

    /**
     * Return list of available payment periods.
     *
     * @guest
     *
     * @return array
     */
    public function periods()
    {
        return \Box_Period::getPredefined();
    }

    /**
     * Gets period title by identifier.
     *
     * @guest
     *
     * @return string
     */
    public function period_title($data)
    {
        $code = $data['code'] ?? null;
        if ($code == null) {
            return '-';
        }
        return $this->getService()->getPeriod($code);
    }

    /**
     * Returns info for paginator according to list.
     *
     * @guest
     *
     * @return array
     */
    public function paginator($data)
    {
        $midrange = 7;
        $page_param = $data['page_param'] ?? 'page';
        $current_page = $data[$page_param];
        $limit = $data['per_page'];
        $itemsCount = $data['total'];
        $p = new \Box_Paginator($itemsCount, $current_page, $limit, $midrange);
        return $p->toArray();
    }

    /**
     * If called from template file this function returns current url.
     *
     * @guest
     *
     * @return string
     */
    public function current_url()
    {
        return $_SERVER['REQUEST_URI'] ?? null;
    }

    /**
     * Check if passed file name template exists for client area.
     *
     * @guest
     *
     * @return bool
     */
    public function template_exists($data)
    {
        if (!isset($data['file'])) {
            return false;
        }
        return $this->getService()->templateExists($data['file']);
    }

    /**
     * Get current client locale.
     *
     * @guest
     *
     * @return string
     */
    public function locale()
    {
        return i18n::getActiveLocale();
    }

    /**
     * Get and clear pending messages.
     *
     * @guest
     *
     * @return array
     */
    public function get_pending_messages()
    {
        $messages = $this->getService()->getPendingMessages();
        $this->getService()->clearPendingMessages();
        return $messages;
    }
}

<?php
/**
 * Copyright 2022-2025 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Profile;

class Api extends \Api_Abstract
{
    /**
     * Get currently logged in user details.
     */
    public function get()
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            return $this->getService()->getAdminIdentityArray($this->getIdentity());
        }

        $this->requireContext(['client']);
        $clientService = $this->di['mod_service']('client');
        return $clientService->toApiArray($this->getIdentity(), true, $this->getIdentity());
    }

    /**
     * Clear session data and logout from system.
     *
     * @return bool
     */
    public function logout()
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            unset($_COOKIE['BOXADMR']);
            $this->di['session']->destroy('admin');
            $this->di['logger']->info('Admin logged out');
            return true;
        }

        $this->requireContext(['client']);
        return $this->getService()->logoutClient();
    }

    /**
     * Update currently logged in user details.
     *
     * @return bool
     */
    public function update($data)
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            if (!is_null($data['email'])) {
                $data['email'] = $this->di['tools']->validateAndSanitizeEmail($data['email']);
            }
            return $this->getService()->updateAdmin($this->getIdentity(), $data);
        }

        $this->requireContext(['client']);
        if (!is_null($data['email'])) {
            $data['email'] = $this->di['tools']->validateAndSanitizeEmail($data['email']);
        }
        return $this->getService()->updateClient($this->getIdentity(), $data);
    }

    /**
     * Generates new API token for currently logged in staff member.
     *
     * @return bool
     */
    public function generate_api_key($data)
    {
        return $this->getService()->generateNewApiKey($this->getIdentity());
    }

    /**
     * Change password for currently logged in user.
     *
     * @return bool
     */
    public function change_password($data)
    {
        $required = [
            'current_password' => 'Current password required',
            'new_password' => 'New password required',
            'confirm_password' => 'New password confirmation required',
        ];
        $validator = $this->di['validator'];
        $validator->checkRequiredParamsForArray($required, $data);
        $validator->isPasswordStrong($data['new_password']);

        if ($data['new_password'] != $data['confirm_password']) {
            throw new \FOSSBilling\InformationException('Passwords do not match');
        }

        $context = $this->getContext();
        if ($context === 'admin') {
            $staff = $this->getIdentity();
            if (!$this->di['password']->verify($data['current_password'], $staff->pass)) {
                throw new \FOSSBilling\InformationException('Current password incorrect');
            }
            $this->getService()->invalidateSessions();
            return $this->getService()->changeAdminPassword($staff, $data['new_password']);
        }

        $this->requireContext(['client']);
        $client = $this->getIdentity();
        if (!$this->di['password']->verify($data['current_password'], $client->pass)) {
            throw new \FOSSBilling\InformationException('Current password incorrect');
        }
        $this->getService()->invalidateSessions();
        return $this->getService()->changeClientPassword($client, $data['new_password']);
    }

    /**
     * Used to destroy / invalidate all existing sessions for a given user.
     */
    public function destroy_sessions(array $data): bool
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $data['type'] ??= null;
            $data['id'] ??= null;
            return $this->getService()->invalidateSessions($data['type'], $data['id']);
        }

        $this->requireContext(['client']);
        return $this->getService()->invalidateSessions();
    }

    /**
     * Reset API key for a given client.
     *
     * @return string the new API key for the client
     */
    public function api_key_reset($data): string
    {
        $context = $this->getContext();
        if ($context === 'admin') {
            $required = [
                'id' => 'Client ID not passed',
            ];
            $this->di['validator']->checkRequiredParamsForArray($required, $data);
            $client = $this->di['db']->getExistingModelById('Client', $data['id']);
            return $this->getService()->resetApiKey($client);
        }

        $this->requireContext(['client']);
        return $this->getService()->resetApiKey($this->getIdentity());
    }

    /**
     * Retrieve current API key.
     */
    public function api_key_get($data)
    {
        $client = $this->getIdentity();
        return $client->api_token;
    }
}

<?php

declare(strict_types=1);
/**
 * Copyright 2022-2025 FOSSBilling
 * SPDX-License-Identifier: Apache-2.0.
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

namespace Box\Mod\Staff;

class Api extends \Api_Abstract
{
    /**
     * @admin
     */
    public function get_list($data)
    {
        $this->assertAdmin();
        $data['no_cron'] = true;
        [$sql, $params] = $this->getService()->getSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $staff = $this->di['db']->getExistingModelById('Admin', $item['id'], 'Admin is not found');
            $pager['list'][$key] = $this->getService()->toModel_AdminApiArray($staff);
        }
        return $pager;
    }

    /**
     * @admin
     */
    public function get($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'ID is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');
        return $this->getService()->toModel_AdminApiArray($model);
    }

    /**
     * @admin
     */
    public function update($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'ID is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        if (!is_null($data['email'])) {
            $data['email'] = $this->di['tools']->validateAndSanitizeEmail($data['email']);
        }
        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');
        return $this->getService()->update($model, $data);
    }

    /**
     * @admin
     */
    public function delete($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'ID is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');
        return $this->getService()->delete($model);
    }

    /**
     * @admin
     */
    public function change_password($data)
    {
        $this->assertAdmin();
        $required = [
            'id' => 'ID is missing',
            'password' => 'Password required',
            'password_confirm' => 'Password confirmation required',
        ];
        $validator = $this->di['validator'];
        $validator->checkRequiredParamsForArray($required, $data);
        if ($data['password'] != $data['password_confirm']) {
            throw new \FOSSBilling\InformationException('Passwords do not match');
        }
        $validator->isPasswordStrong($data['password']);
        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');
        return $this->getService()->changePassword($model, $data['password']);
    }

    /**
     * @admin
     */
    public function create($data)
    {
        $this->assertAdmin();
        $required = [
            'email' => 'Email param is missing',
            'password' => 'Password param is missing',
            'name' => 'Name param is missing',
            'admin_group_id' => 'Group id is missing',
        ];
        $validator = $this->di['validator'];
        $validator->checkRequiredParamsForArray($required, $data);
        $data['email'] = $this->di['tools']->validateAndSanitizeEmail($data['email']);
        $validator->isPasswordStrong($data['password']);
        return $this->getService()->create($data);
    }

    /**
     * @admin
     */
    public function permissions_get($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'ID is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');
        return $this->getService()->getPermissions($model->id);
    }

    /**
     * @admin
     */
    public function permissions_update($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'ID is missing', 'permissions' => 'Permissions parameter missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('Admin', $data['id'], 'Staff member not found');
        $this->getService()->setPermissions($model->id, $data['permissions']);
        $this->di['logger']->info('Changed staff member %s permissions', $model->id);
        return true;
    }

    /**
     * @admin
     */
    public function group_get_pairs($data)
    {
        $this->assertAdmin();
        return $this->getService()->getAdminGroupPair();
    }

    /**
     * @admin
     */
    public function group_get_list($data)
    {
        $this->assertAdmin();
        [$sql, $params] = $this->getService()->getAdminGroupSearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $model = $this->di['db']->getExistingModelById('AdminGroup', $item['id'], 'Post not found');
            $pager['list'][$key] = $this->getService()->toAdminGroupApiArray($model, false, $this->getIdentity());
        }
        return $pager;
    }

    /**
     * @admin
     */
    public function group_create($data)
    {
        $this->assertAdmin();
        $required = [ 'name' => 'Staff group is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        return $this->getService()->createGroup($data['name']);
    }

    /**
     * @admin
     */
    public function group_get($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'Group id is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('AdminGroup', $data['id'], 'Group not found');
        return $this->getService()->toAdminGroupApiArray($model, true, $this->getIdentity());
    }

    /**
     * @admin
     */
    public function group_delete($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'Group id is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('AdminGroup', $data['id'], 'Group not found');
        return $this->getService()->deleteGroup($model);
    }

    /**
     * @admin
     */
    public function group_update($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'Group id is missing' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('AdminGroup', $data['id'], 'Group not found');
        return $this->getService()->updateGroup($model, $data);
    }

    /**
     * @admin
     */
    public function login_history_get_list($data)
    {
        $this->assertAdmin();
        [$sql, $params] = $this->getService()->getActivityAdminHistorySearchQuery($data);
        $per_page = $data['per_page'] ?? $this->di['pager']->getDefaultPerPage();
        $pager = $this->di['pager']->getPaginatedResultSet($sql, $params, $per_page);
        foreach ($pager['list'] as $key => $item) {
            $activity = $this->di['db']->getExistingModelById('ActivityAdminHistory', $item['id'], sprintf('Staff activity item #%s not found', $item['id']));
            if ($activity) {
                $pager['list'][$key] = $this->getService()->toActivityAdminHistoryApiArray($activity);
            }
        }
        return $pager;
    }

    /**
     * @admin
     */
    public function login_history_get($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'Id not passed' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('ActivityAdminHistory', $data['id'], 'Event not found');
        return $this->getService()->toActivityAdminHistoryApiArray($model);
    }

    /**
     * @admin
     */
    public function login_history_delete($data)
    {
        $this->assertAdmin();
        $required = [ 'id' => 'Id not passed' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        $model = $this->di['db']->getExistingModelById('ActivityAdminHistory', $data['id'], 'Event not found');
        return $this->getService()->deleteLoginHistory($model);
    }

    /**
     * @admin
     */
    public function batch_delete_logs($data)
    {
        $this->assertAdmin();
        $required = [ 'ids' => 'IDs not passed' ];
        $this->di['validator']->checkRequiredParamsForArray($required, $data);
        foreach ($data['ids'] as $id) {
            $this->login_history_delete(['id' => $id]);
        }
        return true;
    }

    /**
     * @guest
     */
    public function guest_create($data)
    {
        // Only allow if no admins exist
        $allow = (!is_countable($this->di['db']->findOne('Admin', '1=1')) || count($this->di['db']->findOne('Admin', '1=1')) == 0);
        if (!$allow) {
            throw new \FOSSBilling\InformationException('Administrator account already exists', null, 55);
        }
        $required = [
            'email' => 'Administrator email is missing.',
            'password' => 'Administrator password is missing.',
        ];
        $validator = $this->di['validator'];
        $validator->checkRequiredParamsForArray($required, $data);
        $validator->isPasswordStrong($data['password']);
        if (!is_null($data['email'])) {
            $data['email'] = $this->di['tools']->validateAndSanitizeEmail($data['email']);
        }
        $result = $this->getService()->createAdmin($data);
        if ($result) {
            $this->guest_login($data);
        }
        return true;
    }

    /**
     * @guest
     */
    public function guest_login($data)
    {
        $required = [ 'email' => 'Email required', 'password' => 'Password required' ];
        $validator = $this->di['validator'];
        $validator->checkRequiredParamsForArray($required, $data);
        $data['email'] = $this->di['tools']->validateAndSanitizeEmail($data['email'], true, false);
        $config = $this->getMod()->getConfig();
        if (!empty($config['allowed_ips']) && isset($config['check_ip']) && $config['check_ip']) {
            $allowed_ips = explode(PHP_EOL, $config['allowed_ips']);
            if ($allowed_ips) {
                $allowed_ips = array_map('trim', $allowed_ips);
                if (!in_array($this->getIp(), $allowed_ips)) {
                    throw new \FOSSBilling\InformationException('You are not allowed to login to admin area from :ip address', [':ip' => $this->getIp()], 403);
                }
            }
        }
        $result = $this->getService()->login($data['email'], $data['password'], $this->getIp());
        $this->di['session']->delete('redirect_uri');
        return $result;
    }

    /**
     * @guest
     */
    public function update_password($data)
    {
        $config = $this->getMod()->getConfig();
        if (isset($config['public']['reset_pw']) && $config['public']['reset_pw'] == '0') {
            throw new \FOSSBilling\InformationException('Password reset has been disabled');
        }
        $this->di['events_manager']->fire(['event' => 'onBeforePasswordResetStaff']);
        $required = [
            'code' => 'Code required',
            'password' => 'Password required',
            'password_confirm' => 'Password confirmation required',
        ];
        $validator = $this->di['validator'];
        $validator->checkRequiredParamsForArray($required, $data);
        if ($data['password'] != $data['password_confirm']) {
            throw new \FOSSBilling\InformationException('Passwords do not match');
        }
        $reset = $this->di['db']->findOne('AdminPasswordReset', 'hash = ?', [$data['code']]);
        if (!$reset instanceof \Model_AdminPasswordReset) {
            throw new \FOSSBilling\InformationException('The link has expired or you have already confirmed the password reset.');
        }
        if (strtotime($reset->created_at) - time() + 900 < 0) {
            throw new \FOSSBilling\InformationException('The link has expired or you have already confirmed the password reset.');
        }
        $c = $this->di['db']->getExistingModelById('Admin', $reset->admin_id, 'User not found');
        $c->pass = $this->di['password']->hashIt($data['password']);
        $this->di['db']->store($c);
        $this->di['logger']->info('Admin user requested password reset. Sent to email %s', $c->email);
        $email = [];
        $email['to_admin'] = $c->id;
        $email['code'] = 'mod_staff_password_reset_approve';
        $emailService = $this->di['mod_service']('email');
        $emailService->sendTemplate($email);
        $this->di['db']->trash($reset);
    }

    /**
     * @guest
     */
    public function passwordreset($data)
    {
        $config = $this->getMod()->getConfig();
        if (isset($config['public']['reset_pw']) && $config['public']['reset_pw'] == '0') {
            throw new \FOSSBilling\InformationException('Password reset has been disabled');
        }
        $this->di['events_manager']->fire(['event' => 'onBeforePasswordResetStaff']);
        $required = [ 'email' => 'Email required' ];
        $validator = $this->di['validator'];
        $validator->checkRequiredParamsForArray($required, $data);
        $data['email'] = $this->di['tools']->validateAndSanitizeEmail($data['email']);
        $c = $this->di['db']->findOne('Admin', 'email = ?', [$data['email']]);
        if (!$c instanceof \Model_Admin) {
            return true;
        }
        $hash = hash('sha256', time() . random_bytes(13));
        $c->pass = $hash;
        $reset = $this->di['db']->dispense('AdminPasswordReset');
        $reset->admin_id = $c->id;
        $reset->ip = $this->ip;
        $reset->hash = $hash;
        $reset->created_at = date('Y-m-d H:i:s');
        $reset->updated_at = date('Y-m-d H:i:s');
        $this->di['db']->store($reset);
        $email = [];
        $email['to_admin'] = $c->id;
        $email['code'] = 'mod_staff_password_reset_request';
        $email['hash'] = $hash;
        $emailService = $this->di['mod_service']('email');
        $emailService->sendTemplate($email);
        $this->di['logger']->info('Admin user requested password reset. Sent to email %s', $c->email);
    }
}

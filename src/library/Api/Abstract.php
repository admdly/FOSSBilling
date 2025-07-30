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

use FOSSBilling\InjectionAwareInterface;

class Api_Abstract implements InjectionAwareInterface
{
    /**
     * @var string - request ip
     */
    protected $ip;

    /**
     * @var Box_Mod
     */
    protected $mod;

    // TODO: Find a way to correctly set the type. Maybe a module's service should extend a "Service" class?
    protected $service;

    /**
     * @var Model_Admin|Model_Client|Model_Guest
     */
    protected $identity;

    protected ?Pimple\Container $di = null;

    public function setDi(Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?Pimple\Container
    {
        return $this->di;
    }

    /**
     * @param Box_Mod $mod
     */
    public function setMod($mod)
    {
        $this->mod = $mod;
    }

    /**
     * @return Box_Mod
     */
    public function getMod()
    {
        if (!$this->mod) {
            throw new FOSSBilling\Exception('Mod object is not set for the service');
        }

        return $this->mod;
    }

    /**
     * @param Model_Admin|Model_Client|Model_Guest $identity
     */
    public function setIdentity($identity)
    {
        $this->identity = $identity;
    }

    /**
     * @return Model_Admin
     */
    public function getIdentity()
    {
        return $this->identity;
    }

    // TODO: Find a way to correctly set the type. Maybe a module's service should extend a "Service" class?
    public function setService($service)
    {
        $this->service = $service;
    }

    // TODO: Find a way to correctly set the type. Maybe a module's service should extend a "Service" class?
    public function getService()
    {
        return $this->service;
    }

    /**
     * @param string $ip
     */
    public function setIp($ip)
    {
        $this->ip = $ip;
    }

    /**
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }

    protected function assertAdmin()
    {
        $identity = $this->getIdentity();
        if (!$this->isAdmin($identity)) {
            throw new \FOSSBilling\Exception('Admin privileges required.');
        }
    }

    protected function assertClient()
    {
        $identity = $this->getIdentity();
        if (!$this->isClient($identity)) {
            throw new \FOSSBilling\Exception('Client authentication required.');
        }
    }

    protected function assertAuthenticated()
    {
        $identity = $this->getIdentity();
        if (!$identity) {
            throw new \FOSSBilling\Exception('Authentication required.');
        }
    }

    protected function isAdmin($identity = null)
    {
        $identity = $identity ?? $this->getIdentity();
        return $identity && get_class($identity) === 'Model_Admin';
    }

    protected function isClient($identity = null)
    {
        $identity = $identity ?? $this->getIdentity();
        return $identity && get_class($identity) === 'Model_Client';
    }

    protected function isGuest($identity = null)
    {
        $identity = $identity ?? $this->getIdentity();
        return $identity && get_class($identity) === 'Model_Guest';
    }
}

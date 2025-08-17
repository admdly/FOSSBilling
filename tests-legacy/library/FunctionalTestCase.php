<?php

use FOSSBilling\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FunctionalTestCase extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function createAdminClient(): \Symfony\Bundle\FrameworkBundle\KernelBrowser
    {
        $client = static::createClient();

        $admin = new \Model_Admin();
        $admin->id = 1;
        $admin->email = 'admin@fossbilling.org';
        $admin->name = 'Admin';
        $admin->role = 'admin';

        // The new controllers check for the admin identity in the DI container.
        // We can inject a mock admin model to satisfy the authentication check.
        $di = $GLOBALS['di'];
        $di['loggedin_admin'] = $admin;

        return $client;
    }
}

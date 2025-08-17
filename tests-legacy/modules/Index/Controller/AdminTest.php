<?php

namespace Box\Mod\Index\Controller;

use FunctionalTestCase;

class AdminTest extends FunctionalTestCase
{
    public function testIndex()
    {
        $client = $this->createAdminClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        // The admin dashboard should contain the version number
        $this->assertStringContainsString(\FOSSBilling\Version::VERSION, $responseContent);
    }
}

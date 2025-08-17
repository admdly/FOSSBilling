<?php

namespace Box\Tests\Mod\Activity\Controller;

use FunctionalTestCase;

class AdminTest extends FunctionalTestCase
{
    public function testAdminCanAccessActivityPage()
    {
        $client = $this->createAdminClient();
        $client->request('GET', '/activity');

        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $this->assertStringContainsString('Event history', $responseContent);
    }
}

<?php

namespace Box\Mod\Currency\Controller;

use FunctionalTestCase;

class AdminTest extends FunctionalTestCase
{
    public function testManagePage()
    {
        $client = $this->createAdminClient();
        $client->request('GET', '/currency/manage/USD');

        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $this->assertStringContainsString('Update currency', $responseContent);
    }
}

<?php

namespace Box\Mod\Email\Controller;

use FunctionalTestCase;

class AdminTest extends FunctionalTestCase
{
    public function testHistoryPage()
    {
        $client = $this->createAdminClient();
        $client->request('GET', '/email/history');
        $this->assertResponseIsSuccessful();
    }

    public function testTemplatesPage()
    {
        $client = $this->createAdminClient();
        $client->request('GET', '/email/templates');
        $this->assertResponseIsSuccessful();
    }

    public function testTemplatePage()
    {
        $client = $this->createAdminClient();
        // Assuming template with ID 1 exists from fixtures/seed data
        $client->request('GET', '/email/template/1');
        $this->assertResponseIsSuccessful();
    }

    public function testEmailPage()
    {
        $client = $this->createAdminClient();
        // Assuming email with ID 1 exists from fixtures/seed data
        $client->request('GET', '/email/1');
        $this->assertResponseIsSuccessful();
    }
}

<?php

namespace Box\Mod\Theme\Controller;

use FunctionalTestCase;

class AdminTest extends FunctionalTestCase
{
    public function testThemeSettingsPage()
    {
        $client = $this->createAdminClient();
        $client->request('GET', '/theme/admin_default');

        $this->assertResponseIsSuccessful();
        $responseContent = $client->getResponse()->getContent();
        $this->assertStringContainsString('Theme settings', $responseContent);
    }
}

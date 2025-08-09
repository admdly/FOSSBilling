<?php

namespace Box\Mod\Cron\Tests\Api;

class GuestTest extends \BBTestCase
{
    public function testgetDi(): void
    {
        $di = new \Pimple\Container();
        $api = new \Box\Mod\Cron\Api\Guest();
        $api->setDi($di);
        $getDi = $api->getDi();
        $this->assertEquals($di, $getDi);
    }

    public function testsettings(): void
    {
        $modMock = $this->getMockBuilder('\Box_Mod')->disableOriginalConstructor()->getMock();
        $modMock->expects($this->atLeastOnce())->method('getConfig')->willReturn([]);

        $api = new \Box\Mod\Cron\Api\Guest();
        $api->setMod($modMock);

        $result = $api->settings();
        $this->assertIsArray($result);
    }

    public function testisLate(): void
    {
        $serviceMock = $this->getMockBuilder('\\' . \Box\Mod\Cron\Service::class)->getMock();
        $serviceMock->expects($this->atLeastOnce())->method('isLate')->willReturn(true);

        $api = new \Box\Mod\Cron\Api\Guest();
        $api->setService($serviceMock);

        $result = $api->is_late();
        $this->assertIsBool($result);
        $this->assertTrue($result);
    }
}

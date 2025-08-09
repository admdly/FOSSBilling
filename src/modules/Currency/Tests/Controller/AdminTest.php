<?php

namespace Box\Mod\Currency\Tests\Controller;

class AdminTest extends \BBTestCase
{
    public function testDi(): void
    {
        $controller = new \Box\Mod\Currency\Controller\Admin();

        $di = new \Pimple\Container();
        $db = $this->getMockBuilder('Box_Database')->getMock();

        $di['db'] = $db;
        $controller->setDi($di);
        $result = $controller->getDi();
        $this->assertEquals($di, $result);
    }

    public function testregister(): void
    {
        $boxAppMock = $this->getMockBuilder('\Box_App')->disableOriginalConstructor()->getMock();
        $boxAppMock->expects($this->once())
            ->method('get')
            ->with('/currency/manage/:code', 'get_manage', ['code' => '[a-zA-Z]+'], \Box\Mod\Currency\Controller\Admin::class);

        $controllerAdmin = new \Box\Mod\Currency\Controller\Admin();
        $controllerAdmin->register($boxAppMock);
    }
}

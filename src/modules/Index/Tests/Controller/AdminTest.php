<?php

namespace Box\Mod\Index\Tests\Controller;

class AdminTest extends \BBTestCase
{
    public function testDi(): void
    {
        $controller = new \Box\Mod\Index\Controller\Admin();

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
        $boxAppMock->expects($this->exactly(4))
            ->method('get');

        $controller = new \Box\Mod\Index\Controller\Admin();
        $controller->register($boxAppMock);
    }

    public function testgetIndexAdminIsLogged(): void
    {
        $boxAppMock = $this->getMockBuilder('\Box_App')->disableOriginalConstructor()->getMock();
        $boxAppMock->expects($this->atLeastOnce())
            ->method('render')
            ->with('mod_index_dashboard');

        $authorizationMock = $this->getMockBuilder('\Box_Authorization')->disableOriginalConstructor()->getMock();
        $authorizationMock->expects($this->atLeastOnce())
            ->method('isAdminLoggedIn')
            ->willReturn(true);

        $di = new \Pimple\Container();
        $di['auth'] = $authorizationMock;

        $controller = new \Box\Mod\Index\Controller\Admin();
        $controller->setDi($di);
        $controller->get_index($boxAppMock);
    }
}

<?php

namespace Box\Mod\Order\Tests\Api;

use Box\Mod\Order\Api\Client;
use Pimple\Container;
use Model_ClientOrder;
use Model_Product;
use Model_Client;
use FOSSBilling\Pagination;
use FOSSBilling\Validate;
use FOSSBilling\Exception;
use RedBeanPHP\OODBBean;

/**
 * @group Order
 */
class ClientTest extends \BBTestCase
{
    /**
     * @var Client
     */
    protected $api;

    public function setup(): void
    {
        $this->api = new Client();
    }

    public function testgetDi(): void
    {
        $di = new Container();
        $this->api->setDi($di);
        $getDi = $this->api->getDi();
        $this->assertEquals($di, $getDi);
    }

    public function testGetList(): void
    {
        $serviceMock = $this->getMockBuilder(\Box\Mod\Order\Service::class)
            ->onlyMethods(['getSearchQuery', 'toApiArray'])->getMock();
        $serviceMock->expects($this->atLeastOnce())->method('getSearchQuery')
            ->willReturn(['query', []]);
        $serviceMock->expects($this->atLeastOnce())->method('toApiArray')
            ->willReturn([]);

        $resultSet = [
            'list' => [
                0 => ['id' => 1],
            ],
        ];
        $paginatorMock = $this->getMockBuilder(Pagination::class)
        ->onlyMethods(['getPaginatedResultSet'])
        ->disableOriginalConstructor()
        ->getMock();
        $paginatorMock->expects($this->atLeastOnce())
            ->method('getPaginatedResultSet')
            ->willReturn($resultSet);

        $clientOrderMock = new Model_ClientOrder();
        $clientOrderMock->loadBean(new \DummyBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getExistingModelById')
            ->with('ClientOrder')
            ->willReturn($clientOrderMock);

        $di = new Container();
        $di['pager'] = $paginatorMock;
        $di['db'] = $dbMock;

        $this->api->setDi($di);

        $client = new Model_Client();
        $client->loadBean(new \DummyBean());
        $client->id = random_int(1, 100);

        $this->api->setIdentity($client);
        $this->api->setService($serviceMock);

        $result = $this->api->get_list([]);

        $this->assertIsArray($result);
    }

    public function testGetListExpiring(): void
    {
        $serviceMock = $this->getMockBuilder(\Box\Mod\Order\Service::class)
            ->onlyMethods(['getSoonExpiringActiveOrdersQuery'])->getMock();
        $serviceMock->expects($this->atLeastOnce())->method('getSoonExpiringActiveOrdersQuery')
            ->willReturn(['query', []]);

        $paginatorMock = $this->getMockBuilder(Pagination::class)
        ->onlyMethods(['getPaginatedResultSet'])
        ->disableOriginalConstructor()
        ->getMock();
        $paginatorMock->expects($this->atLeastOnce())
            ->method('getPaginatedResultSet')
            ->willReturn(['list' => []]);

        $di = new Container();
        $di['pager'] = $paginatorMock;

        $this->api->setDi($di);

        $client = new Model_Client();
        $client->loadBean(new \DummyBean());
        $client->id = random_int(1, 100);

        $this->api->setIdentity($client);
        $this->api->setService($serviceMock);

        $data = [
            'expiring' => true,
        ];
        $result = $this->api->get_list($data);

        $this->assertIsArray($result);
    }

    public function testGet(): void
    {
        $order = new Model_ClientOrder();
        $order->loadBean(new \DummyBean());

        $apiMock = $this->getMockBuilder(Client::class)->onlyMethods(['_getOrder'])->disableOriginalConstructor()->getMock();
        $apiMock->expects($this->atLeastOnce())
            ->method('_getOrder')
            ->willReturn($order);

        $serviceMock = $this->getMockBuilder(\Box\Mod\Order\Service::class)
            ->onlyMethods(['toApiArray'])->getMock();
        $serviceMock->expects($this->atLeastOnce())->method('toApiArray')
            ->willReturn([]);

        $apiMock->setService($serviceMock);

        $data = [
            'id' => random_int(1, 100),
        ];
        $result = $apiMock->get($data);

        $this->assertIsArray($result);
    }

    public function testAddons(): void
    {
        $serviceMock = $this->getMockBuilder(\Box\Mod\Order\Service::class)
            ->onlyMethods(['getOrderAddonsList', 'toApiArray'])->getMock();
        $serviceMock->expects($this->atLeastOnce())->method('getOrderAddonsList')
            ->willReturn([new Model_ClientOrder()]);
        $serviceMock->expects($this->atLeastOnce())->method('toApiArray')
            ->willReturn([]);

        $order = new Model_ClientOrder();
        $order->loadBean(new \DummyBean());

        $apiMock = $this->getMockBuilder(Client::class)->onlyMethods(['_getOrder'])->disableOriginalConstructor()->getMock();
        $apiMock->expects($this->atLeastOnce())
            ->method('_getOrder')
            ->willReturn($order);

        $apiMock->setService($serviceMock);

        $data = [
            'status' => Model_ClientOrder::STATUS_ACTIVE,
        ];
        $result = $apiMock->addons($data);

        $this->assertIsArray($result);
        $this->assertIsArray($result[0]);
    }

    public function testService(): void
    {
        $order = new Model_ClientOrder();
        $order->loadBean(new \DummyBean());

        $apiMock = $this->getMockBuilder(Client::class)->onlyMethods(['_getOrder'])->disableOriginalConstructor()->getMock();
        $apiMock->expects($this->atLeastOnce())
            ->method('_getOrder')
            ->willReturn($order);

        $serviceMock = $this->getMockBuilder(\Box\Mod\Order\Service::class)
            ->onlyMethods(['getOrderServiceData'])->getMock();
        $serviceMock->expects($this->atLeastOnce())->method('getOrderServiceData')
            ->willReturn([]);

        $client = new Model_Client();
        $client->loadBean(new \DummyBean());

        $apiMock->setService($serviceMock);
        $apiMock->setIdentity($client);

        $data = [
            'id' => random_int(1, 100),
        ];
        $result = $apiMock->service($data);

        $this->assertIsArray($result);
    }

    public function testUpgradables(): void
    {
        $order = new Model_ClientOrder();
        $order->loadBean(new \DummyBean());

        $apiMock = $this->getMockBuilder(Client::class)->onlyMethods(['_getOrder'])->disableOriginalConstructor()->getMock();
        $apiMock->expects($this->atLeastOnce())
            ->method('_getOrder')
            ->willReturn($order);

        $productServiceMock = $this->getMockBuilder(\Box\Mod\Product\Service::class)->onlyMethods(['getUpgradablePairs'])->getMock();
        $productServiceMock->expects($this->atLeastOnce())
            ->method('getUpgradablePairs')
            ->willReturn([]);

        $product = new Model_Product();
        $product->loadBean(new OODBBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->disableOriginalConstructor()->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getExistingModelById')
            ->willReturn($product);

        $di = new Container();
        $di['db'] = $dbMock;
        $di['mod_service'] = $di->protect(fn () => $productServiceMock);
        $apiMock->setDi($di);
        $data = [];

        $result = $apiMock->upgradables($data);
        $this->assertIsArray($result);
    }

    public function testDelete(): void
    {
        $order = new Model_ClientOrder();
        $order->loadBean(new \DummyBean());
        $order->status = Model_ClientOrder::STATUS_PENDING_SETUP;

        $apiMock = $this->getMockBuilder(Client::class)->onlyMethods(['_getOrder'])->disableOriginalConstructor()->getMock();
        $apiMock->expects($this->atLeastOnce())
            ->method('_getOrder')
            ->willReturn($order);

        $serviceMock = $this->getMockBuilder(\Box\Mod\Order\Service::class)
            ->onlyMethods(['deleteFromOrder'])->getMock();
        $serviceMock->expects($this->atLeastOnce())->method('deleteFromOrder')
            ->willReturn(true);

        $apiMock->setService($serviceMock);

        $data = [
            'id' => random_int(1, 100),
        ];
        $result = $apiMock->delete($data);

        $this->assertTrue($result);
    }

    public function testDeleteNotPendingException(): void
    {
        $order = new Model_ClientOrder();
        $order->loadBean(new \DummyBean());

        $apiMock = $this->getMockBuilder(Client::class)->onlyMethods(['_getOrder'])->disableOriginalConstructor()->getMock();
        $apiMock->expects($this->atLeastOnce())
            ->method('_getOrder')
            ->willReturn($order);

        $serviceMock = $this->getMockBuilder(\Box\Mod\Order\Service::class)
            ->onlyMethods(['deleteFromOrder'])->getMock();
        $serviceMock->expects($this->never())->method('deleteFromOrder')
            ->willReturn(true);

        $apiMock->setService($serviceMock);

        $data = [
            'id' => random_int(1, 100),
        ];

        $this->expectException(Exception::class);
        $result = $apiMock->delete($data);

        $this->assertTrue($result);
    }

    public function testGetOrder(): void
    {
        $validatorMock = $this->getMockBuilder(Validate::class)->disableOriginalConstructor()->getMock();
        $validatorMock->expects($this->atLeastOnce())
            ->method('checkRequiredParamsForArray')
            ->willReturn(null);

        $order = new Model_ClientOrder();
        $order->loadBean(new \DummyBean());

        $serviceMock = $this->getMockBuilder(\Box\Mod\Order\Service::class)
            ->onlyMethods(['findForClientById', 'toApiArray'])->getMock();
        $serviceMock->expects($this->atLeastOnce())->method('findForClientById')
            ->willReturn($order);
        $serviceMock->expects($this->atLeastOnce())->method('toApiArray')
            ->willReturn([]);

        $order = new Model_ClientOrder();
        $order->loadBean(new \DummyBean());

        $client = new Model_Client();
        $client->loadBean(new \DummyBean());

        $di = new Container();
        $di['validator'] = $validatorMock;
        $this->api->setDi($di);

        $this->api->setService($serviceMock);
        $this->api->setIdentity($client);

        $data = [
            'id' => random_int(1, 100),
        ];
        $this->api->get($data);
    }

    public function testGetOrderNotFoundException(): void
    {
        $validatorMock = $this->getMockBuilder(Validate::class)->disableOriginalConstructor()->getMock();
        $validatorMock->expects($this->atLeastOnce())
            ->method('checkRequiredParamsForArray')
            ->willReturn(null);

        $serviceMock = $this->getMockBuilder(\Box\Mod\Order\Service::class)
            ->onlyMethods(['findForClientById', 'toApiArray'])->getMock();
        $serviceMock->expects($this->atLeastOnce())->method('findForClientById')
            ->willReturn(null);
        $serviceMock->expects($this->never())->method('toApiArray')
            ->willReturn([]);

        $order = new Model_ClientOrder();
        $order->loadBean(new \DummyBean());

        $client = new Model_Client();
        $client->loadBean(new \DummyBean());

        $di = new Container();
        $di['validator'] = $validatorMock;
        $this->api->setDi($di);

        $this->api->setService($serviceMock);
        $this->api->setIdentity($client);

        $data = [
            'id' => random_int(1, 100),
        ];

        $this->expectException(Exception::class);
        $this->api->get($data);
    }
}

<?php

namespace Box\Mod\Billing;

class ServiceTest extends \BBTestCase
{
    /**
     * @var Service
     */
    protected $service;

    public function setup(): void
    {
        $this->service = new Service();
    }

    public function testCreate()
    {
        $data = [
            'client_id' => 1,
            'invoice_id' => 1,
            'gateway_id' => 1,
            'amount' => '10.00',
            'currency' => 'USD',
            'type' => 'payment',
            'status' => 'processed',
        ];

        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());
        $newId = 1;

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->once())
            ->method('dispense')
            ->with('Transaction')
            ->willReturn($transactionModel);

        $dbMock->expects($this->once())
            ->method('store')
            ->with($transactionModel)
            ->willReturn($newId);

        $eventManagerMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventManagerMock->expects($this->exactly(2))
            ->method('fire');

        $requestMock = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')->getMock();
        $requestMock->expects($this->once())
            ->method('getClientIp')
            ->willReturn('127.0.0.1');

        $di = new \Pimple\Container();
        $di['db'] = $dbMock;
        $di['events_manager'] = $eventManagerMock;
        $di['request'] = $requestMock;
        $di['logger'] = new \Box_Log();

        $this->service->setDi($di);

        $result = $this->service->create($data);

        $this->assertEquals($newId, $result);
    }
}

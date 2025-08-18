<?php

namespace Box\Mod\Invoice;

class ServiceTransactionTest extends \BBTestCase
{
    /**
     * @var ServiceTransaction
     */
    protected $service;

    public function setup(): void
    {
        $this->service = new ServiceTransaction();
    }

    public function testgetDi(): void
    {
        $di = new \Pimple\Container();
        $this->service->setDi($di);
        $getDi = $this->service->getDi();
        $this->assertEquals($di, $getDi);
    }

    public function testprocessReceivedATransactions(): void
    {
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $serviceMock = $this->getMockBuilder('\\' . ServiceTransaction::class)
            ->onlyMethods(['getReceived', 'preProcessTransaction'])
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('getReceived')
            ->willReturn([[]]);
        $serviceMock->expects($this->atLeastOnce())
            ->method('preProcessTransaction');

        $dbMock = $this->getMockBuilder('\Box_Database')
            ->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getExistingModelById')
            ->will($this->onConsecutiveCalls($transactionModel));

        $di = new \Pimple\Container();
        $di['logger'] = new \Box_Log();
        $di['db'] = $dbMock;

        $serviceMock->setDi($di);
        $result = $serviceMock->processReceivedATransactions();
        $this->assertTrue($result);
    }

    public function testupdate(): void
    {
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $billingServiceMock = $this->getMockBuilder('\\' . \Box\Mod\Billing\Service::class)->getMock();
        $billingServiceMock->expects($this->atLeastOnce())
            ->method('update')
            ->with($transactionModel, [])
            ->willReturn(true);

        $di = new \Pimple\Container();
        $di['mod_service'] = $di->protect(fn($name) => $billingServiceMock);

        $this->service->setDi($di);

        $result = $this->service->update($transactionModel, []);
        $this->assertTrue($result);
    }

    public function testCreate(): void
    {
        $data = [
            'skip_validation' => false,
            'gateway_id' => 1,
            'invoice_id' => 2,
        ];

        $billingServiceMock = $this->getMockBuilder('\\' . \Box\Mod\Billing\Service::class)->getMock();
        $billingServiceMock->expects($this->atLeastOnce())
            ->method('create')
            ->with($data)
            ->willReturn(1);

        $di = new \Pimple\Container();
        $di['mod_service'] = $di->protect(fn($name) => $billingServiceMock);

        $this->service->setDi($di);

        $result = $this->service->create($data);
        $this->assertIsInt($result);
    }


    public function testdelete(): void
    {
        $dbMock = $this->getMockBuilder('\Box_Database')
            ->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('trash');

        $di = new \Pimple\Container();
        $di['logger'] = new \Box_Log();
        $di['db'] = $dbMock;
        $this->service->setDi($di);

        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $result = $this->service->delete($transactionModel);
        $this->assertTrue($result);
    }

    public function testtoApiArray(): void
    {
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $billingServiceMock = $this->getMockBuilder('\\' . \Box\Mod\Billing\Service::class)->getMock();
        $billingServiceMock->expects($this->atLeastOnce())
            ->method('toApiArray')
            ->with($transactionModel, false, null)
            ->willReturn([]);

        $di = new \Pimple\Container();
        $di['mod_service'] = $di->protect(fn($name) => $billingServiceMock);

        $this->service->setDi($di);

        $result = $this->service->toApiArray($transactionModel, false, null);
        $this->assertIsArray($result);
    }

    public function testgetSearchQuery(): void
    {
        $data = [];

        $billingServiceMock = $this->getMockBuilder('\\' . \Box\Mod\Billing\Service::class)->getMock();
        $billingServiceMock->expects($this->atLeastOnce())
            ->method('getSearchQuery')
            ->with($data)
            ->willReturn(['', []]);

        $di = new \Pimple\Container();
        $di['mod_service'] = $di->protect(fn($name) => $billingServiceMock);

        $this->service->setDi($di);

        $result = $this->service->getSearchQuery($data);
        $this->assertIsArray($result);
    }

    public function testcounter(): void
    {
        $queryResult = [['status' => \Model_Transaction::STATUS_RECEIVED, 'counter' => 1]];
        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('getAll')
            ->willReturn($queryResult);

        $di = new \Pimple\Container();
        $di['db'] = $dbMock;
        $this->service->setDi($di);

        $result = $this->service->counter();
        $this->assertIsArray($result);
        $expected = [
            'total' => 1,
            'received' => 1,
            'approved' => 0,
            'error' => 0,
            'processed' => 0,
        ];
        $this->assertEquals($expected, $result);
    }

    public function testgetStatusPairs(): void
    {
        $result = $this->service->getStatusPairs();
        $this->assertIsArray($result);

        $expected = [
            'received' => 'Received',
            'approved' => 'Approved',
            'processed' => 'Processed',
            'error' => 'Error',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testgetStatus(): void
    {
        $result = $this->service->getStatuses();
        $this->assertIsArray($result);

        $expected = [
            'received' => 'Received',
            'approved' => 'Approved/Verified',
            'processed' => 'Processed',
            'error' => 'Error',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testgetGatewayStatuses(): void
    {
        $result = $this->service->getGatewayStatuses();
        $this->assertIsArray($result);

        $expected = [
            'pending' => 'Pending validation',
            'complete' => 'Complete',
            'unknown' => 'Unknown',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testgetTypes(): void
    {
        $result = $this->service->getTypes();
        $this->assertIsArray($result);

        $expected = [
            'payment' => 'Payment',
            'refund' => 'Refund',
            'subscription_create' => 'Subscription create',
            'subscription_cancel' => 'Subscription cancel',
            'unknown' => 'Unknown',
        ];
        $this->assertEquals($expected, $result);
    }

    public function testpreProcessTransaction(): void
    {
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $serviceMock = $this->getMockBuilder('\\' . ServiceTransaction::class)
            ->onlyMethods(['processTransaction'])
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('processTransaction')
            ->willReturn('processedOutputString');

        $eventMock = $this->getMockBuilder('\Box_EventManager')->getMock();
        $eventMock->expects($this->atLeastOnce())
            ->method('fire');

        $di = new \Pimple\Container();
        $di['events_manager'] = $eventMock;
        $di['logger'] = new \Box_Log();
        $serviceMock->setDi($di);

        $result = $serviceMock->preProcessTransaction($transactionModel);
        $this->assertIsString($result);
    }

    public function testpreProcessTransactionRegisterException(): void
    {
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());

        $exceptionMessage = 'Exception created with PHPUnit Test';

        $serviceMock = $this->getMockBuilder('\\' . ServiceTransaction::class)
            ->addMethods(['oldProcessLogic'])
            ->onlyMethods(['processTransaction'])
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('processTransaction')
            ->will($this->throwException(new \FOSSBilling\Exception($exceptionMessage)));

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('store');

        $di = new \Pimple\Container();
        $di['db'] = $dbMock;
        $serviceMock->setDi($di);

        $this->expectException(\FOSSBilling\Exception::class);
        $this->expectExceptionMessage($exceptionMessage);
        $serviceMock->preProcessTransaction($transactionModel);
    }

    public static function paymentsAdapterProvider_withprocessTransaction()
    {
        return [
            ['\Payment_Adapter_PayPalEmail'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('paymentsAdapterProvider_withprocessTransaction')]
    public function testprocessTransactionSupportProcessTransaction($adapter): void
    {
        $id = 1;
        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());
        $transactionModel->gateway_id = 2;
        $transactionModel->ipn = '{}';

        $payGatewayModel = new \Model_PayGateway();
        $payGatewayModel->loadBean(new \DummyBean());

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')
            ->will($this->onConsecutiveCalls($transactionModel, $payGatewayModel));

        $paymentAdapterMock = $this->getMockBuilder($adapter)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentAdapterMock->expects($this->atLeastOnce())
            ->method('processTransaction');

        $payGatewayService = $this->getMockBuilder('\\' . ServicePayGateway::class)->getMock();
        $payGatewayService->expects($this->atLeastOnce())
            ->method('getPaymentAdapter')
            ->willReturn($paymentAdapterMock);

        $di = new \Pimple\Container();
        $di['db'] = $dbMock;
        $di['mod_service'] = $di->protect(fn () => $payGatewayService);
        $di['api_system'] = new \Api_Handler(new \Model_Admin());
        $this->service->setDi($di);

        $this->service->processTransaction($id);
    }

    public function getReceived()
    {
        $serviceMock = $this->getMockBuilder('\\' . ServiceTransaction::class)
            ->onlyMethods(['getSearchQuery'])
            ->getMock();
        $serviceMock->expects($this->atLeastOnce())
            ->method('getSearchQuery')
            ->willReturn(['SqlString', []]);

        $assoc = [
            [
                'id' => 1,
                'invoice_id' => 1,
            ],
        ];
        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->expects($this->atLeastOnce())
            ->method('load')
            ->willReturn($assoc);

        $transactionModel = new \Model_Transaction();
        $transactionModel->loadBean(new \DummyBean());
        $dbMock->expects($this->atLeastOnce())
            ->method('getAll')
            ->willReturn([[]]);

        $di = new \Pimple\Container();
        $di['db'] = $dbMock;
        $serviceMock->setDi($di);

        $result = $serviceMock->getReceived();
        $this->assertIsArray($result);
    }

//    public function testdebitTransaction(): void
//    {
//        $currency = 'EUR';
//        $invoiceModel = new \Model_Invoice();
//        $invoiceModel->loadBean(new \DummyBean());
//        $invoiceModel->currency = $currency;
//
//        $clientModdel = new \Model_Client();
//        $clientModdel->loadBean(new \DummyBean());
//        $clientModdel->currency = $currency;
//
//        $transactionModel = new \Model_Transaction();
//        $transactionModel->loadBean(new \DummyBean());
//        $transactionModel->amount = 11;
//
//        $clientBalanceModel = new \Model_ClientBalance();
//        $clientBalanceModel->loadBean(new \DummyBean());
//
//        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
//        $dbMock->expects($this->atLeastOnce())
//            ->method('load')
//            ->will($this->onConsecutiveCalls($invoiceModel, $clientModdel));
//        $dbMock->expects($this->atLeastOnce())
//            ->method('dispense')
//            ->willReturn($clientBalanceModel);
//        $dbMock->expects($this->atLeastOnce())
//            ->method('store');
//
//        $di = new \Pimple\Container();
//        $di['db'] = $dbMock;
//        $this->service->setDi($di);
//
//        $this->service->debitTransaction($transactionModel);
//    }

    public function testcreateAndProcess(): void
    {
        $serviceMock = $this->getMockBuilder('\\' . ServiceTransaction::class)
            ->onlyMethods(['create', 'processTransaction'])
            ->getMock();
        $serviceMock->expects($this->once())
            ->method('create');
        $serviceMock->expects($this->once())
            ->method('processTransaction');

        $ipn = [];
        $serviceMock->createAndProcess($ipn);
    }
}

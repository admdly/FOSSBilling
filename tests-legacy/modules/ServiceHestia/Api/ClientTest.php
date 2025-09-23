<?php

namespace Box\Mod\ServiceHestia\Api;

class ClientTest extends \BBTestCase
{
    public function testListWebDomains()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->list_web_domains(['order_id' => 1]);
        $this->assertIsArray($result);
    }

    public function testAddWebDomain()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->add_web_domain(['order_id' => 1, 'domain' => 'example.com']);
        $this->assertNull($result);
    }

    public function testDeleteWebDomain()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->delete_web_domain(['order_id' => 1, 'domain' => 'example.com']);
        $this->assertNull($result);
    }

    public function testAddWebDomainAlias()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->add_web_domain_alias(['order_id' => 1, 'domain' => 'example.com', 'alias' => 'alias.com']);
        $this->assertNull($result);
    }

    public function testDeleteWebDomainAlias()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->delete_web_domain_alias(['order_id' => 1, 'domain' => 'example.com', 'alias' => 'alias.com']);
        $this->assertNull($result);
    }

    public function testAddLetsEncrypt()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->add_lets_encrypt(['order_id' => 1, 'domain' => 'example.com']);
        $this->assertNull($result);
    }

    public function testDeleteLetsEncrypt()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->delete_lets_encrypt(['order_id' => 1, 'domain' => 'example.com']);
        $this->assertNull($result);
    }

    public function testListWebBackendTemplates()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->list_web_backend_templates(['order_id' => 1]);
        $this->assertIsArray($result);
    }

    public function testChangeBackendTpl()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->change_backend_tpl(['order_id' => 1, 'domain' => 'example.com', 'template' => 'PHP-8_1']);
        $this->assertNull($result);
    }

    public function testListCronJobs()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->list_cron_jobs(['order_id' => 1]);
        $this->assertIsArray($result);
    }

    public function testAddCronJob()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->add_cron_job(['order_id' => 1, 'command' => 'ls -la', 'schedule' => '* * * * *']);
        $this->assertNull($result);
    }

    public function testDeleteCronJob()
    {
        $clientApi = $this->getMockClientApi();
        $result = $clientApi->delete_cron_job(['order_id' => 1, 'job_id' => '1']);
        $this->assertNull($result);
    }


    private function getMockClientApi()
    {
        $clientApi = new Client();

        $clientOrder = new \Model_ClientOrder();
        $clientOrder->loadBean(new \DummyBean());
        $clientOrder->client_id = 1;

        $dbMock = $this->getMockBuilder('\Box_Database')->getMock();
        $dbMock->method('getExistingModelById')->willReturn($clientOrder);

        $hestiaManagerMock = $this->getMockBuilder(\Server_Manager_Hestia::class)
            ->setConstructorArgs([['host' => 'testhost.com']])
            ->getMock();
        $hestiaManagerMock->method('listWebDomains')->willReturn([]);
        $hestiaManagerMock->method('listWebBackendTemplates')->willReturn([]);
        $hestiaManagerMock->method('listCronJobs')->willReturn([]);

        $serviceMock = $this->getMockBuilder(\Box\Mod\ServiceHestia\Service::class)->getMock();
        $serviceMock->method('_get_manager_and_account')->willReturn([$hestiaManagerMock, new \Server_Account()]);

        $validatorMock = $this->getMockBuilder('\FOSSBilling\Validate')->getMock();

        $di = new \Pimple\Container();
        $di['db'] = $dbMock;
        $di['validator'] = $validatorMock;

        $clientApi->setDi($di);
        $clientApi->setService($serviceMock);

        $identity = new \Model_Client();
        $identity->loadBean(new \DummyBean());
        $identity->id = 1;
        $clientApi->setIdentity($identity);

        return $clientApi;
    }
}

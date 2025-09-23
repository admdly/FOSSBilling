<?php

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Server_Manager_HestiaTest extends PHPUnit\Framework\TestCase
{
    private function getManagerMock(MockResponse $response)
    {
        $httpClient = new MockHttpClient($response);
        $manager = $this->getMockBuilder(Server_Manager_Hestia::class)
            ->setConstructorArgs([['host' => 'testhost.com']])
            ->onlyMethods(['getHttpClient'])
            ->getMock();
        $manager->method('getHttpClient')->willReturn($httpClient);
        return $manager;
    }

    private function getAccountMock()
    {
        $account = $this->getMockBuilder(Server_Account::class)->disableOriginalConstructor()->getMock();
        $account->method('getUsername')->willReturn('testuser');
        return $account;
    }

    public function testListWebDomains()
    {
        $json = '{"example.com": {"ALIAS": "alias.example.com"}}';
        $manager = $this->getManagerMock(new MockResponse($json));
        $account = $this->getAccountMock();
        $result = $manager->listWebDomains($account);
        $this->assertEquals(json_decode($json, true), $result);
    }

    public function testAddWebDomain()
    {
        $manager = $this->getManagerMock(new MockResponse('0'));
        $account = $this->getAccountMock();
        $result = $manager->addWebDomain($account, 'example.com');
        $this->assertEquals('0', $result);
    }

    public function testDeleteWebDomain()
    {
        $manager = $this->getManagerMock(new MockResponse('0'));
        $account = $this->getAccountMock();
        $result = $manager->deleteWebDomain($account, 'example.com');
        $this->assertEquals('0', $result);
    }

    public function testAddWebDomainAlias()
    {
        $manager = $this->getManagerMock(new MockResponse('0'));
        $account = $this->getAccountMock();
        $result = $manager->addWebDomainAlias($account, 'example.com', 'alias.example.com');
        $this->assertEquals('0', $result);
    }

    public function testDeleteWebDomainAlias()
    {
        $manager = $this->getManagerMock(new MockResponse('0'));
        $account = $this->getAccountMock();
        $result = $manager->deleteWebDomainAlias($account, 'example.com', 'alias.example.com');
        $this->assertEquals('0', $result);
    }

    public function testAddLetsEncryptDomain()
    {
        $manager = $this->getManagerMock(new MockResponse('0'));
        $account = $this->getAccountMock();
        $result = $manager->addLetsEncryptDomain($account, 'example.com');
        $this->assertEquals('0', $result);
    }

    public function testDeleteLetsEncryptDomain()
    {
        $manager = $this->getManagerMock(new MockResponse('0'));
        $account = $this->getAccountMock();
        $result = $manager->deleteLetsEncryptDomain($account, 'example.com');
        $this->assertEquals('0', $result);
    }

    public function testChangeWebDomainBackendTpl()
    {
        $manager = $this->getManagerMock(new MockResponse('0'));
        $account = $this->getAccountMock();
        $result = $manager->changeWebDomainBackendTpl($account, 'example.com', 'PHP-8_1');
        $this->assertEquals('0', $result);
    }

    public function testListCronJobs()
    {
        $json = '{"1": {"CMD": "ls -la"}}';
        $manager = $this->getManagerMock(new MockResponse($json));
        $account = $this->getAccountMock();
        $result = $manager->listCronJobs($account);
        $this->assertEquals(json_decode($json, true), $result);
    }

    public function testAddCronJob()
    {
        $manager = $this->getManagerMock(new MockResponse('0'));
        $account = $this->getAccountMock();
        $result = $manager->addCronJob($account, 'ls -la', '* * * * *');
        $this->assertEquals('0', $result);
    }

    public function testDeleteCronJob()
    {
        $manager = $this->getManagerMock(new MockResponse('0'));
        $account = $this->getAccountMock();
        $result = $manager->deleteCronJob($account, '1');
        $this->assertEquals('0', $result);
    }

    public function testListWebBackendTemplates()
    {
        $json = '{"PHP-8_1": {}, "PHP-8_0": {}}';
        $manager = $this->getManagerMock(new MockResponse($json));
        $result = $manager->listWebBackendTemplates();
        $this->assertEquals(json_decode($json, true), $result);
    }
}

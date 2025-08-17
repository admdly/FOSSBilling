<?php

namespace Box\Mod\Extension;

class ServiceTest extends \BBDatabaseTestCase
{
    /**
     * @var Service
     */
    protected $service;

    public function setup(): void
    {
        parent::setUp();
        $this->service = new Service();
        $this->service->setDi($this->di);
    }

    public function testgetAdminNavigation()
    {
        $admin = new \Model_Admin();
        $admin->loadBean(new \DummyBean());
        $admin->role = 'admin';

        $result = $this->service->getAdminNavigation($admin);
        $this->assertIsArray($result);
    }

    public static function searchQueryData()
    {
        return [
            [[], 'SELECT * FROM extension', []],
            [['type' => 'mod'], 'AND type = :type', [':type' => 'mod']],
            [['search' => 'FindUp'], 'AND name LIKE :search', [':search' => '%FindUp%']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('searchQueryData')]
    public function testgetSearchQuery($data, $expectedStr, $expectedParams): void
    {
        [$sql, $params] = $this->service->getSearchQuery($data);

        $this->assertIsString($sql);
        $this->assertIsArray($params);

        $this->assertTrue(str_contains($sql, $expectedStr), $sql);
        $this->assertEquals($expectedParams, $params);
    }

    public function testisCoreModule(): void
    {
        $result = $this->service->isCoreModule('cron');
        $this->assertTrue($result);

        $result = $this->service->isCoreModule('not-a-core-module');
        $this->assertFalse($result);
    }
}

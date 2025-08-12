<?php

namespace Box\Mod\Service;

use FOSSBilling\InjectionAwareInterface;
use RedBeanPHP\OODBBean;

abstract class ServiceAbstract implements InjectionAwareInterface
{
    protected ?\Pimple\Container $di = null;

    public function setDi(\Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    abstract public function attachOrderConfig(\Model_Product $product, array $data): array;

    abstract public function create(OODBBean $order);

    abstract public function activate(OODBBean $order, OODBBean $model): bool;

    abstract public function suspend(OODBBean $order, OODBBean $model): bool;

    abstract public function unsuspend(OODBBean $order, OODBBean $model): bool;

    abstract public function cancel(OODBBean $order, OODBBean $model): bool;

    abstract public function uncancel(OODBBean $order, OODBBean $model): bool;

    abstract public function delete(?OODBBean $order, ?OODBBean $model): void;

    abstract public function toApiArray(OODBBean $model): array;
}

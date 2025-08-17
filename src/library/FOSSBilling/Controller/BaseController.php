<?php

declare(strict_types=1);

namespace FOSSBilling\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BaseController extends AbstractController
{
    protected ?\Pimple\Container $di = null;

    public function __construct()
    {
        $this->di = $GLOBALS['di'];
    }
}

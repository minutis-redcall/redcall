<?php

namespace App\Facade\Resource;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class BadgeResourceFacade extends ResourceFacade
{
    public function __construct()
    {
        $this->type = self::TYPE_BADGE;
    }

    public static function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->externalId = 'demo-truck';
        $facade->label      = 'Truck Driver';

        return $facade;
    }
}
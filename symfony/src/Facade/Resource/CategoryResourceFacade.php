<?php

namespace App\Facade\Resource;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class CategoryResourceFacade extends ResourceFacade
{
    public function __construct()
    {
        $this->type = self::TYPE_CATEGORY;
    }

    public static function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->externalId = 'demo-vehicles';
        $facade->label      = 'Vehicles';

        return $facade;
    }
}
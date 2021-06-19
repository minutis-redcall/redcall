<?php

namespace App\Facade\Resource;

use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class VolunteerResourceFacade extends ResourceFacade
{
    public function __construct()
    {
        $this->type = self::TYPE_VOLUNTEER;
    }

    public static function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->externalId = 'demo-volunteer';
        $facade->label      = 'John Doe';

        return $facade;
    }
}
<?php

namespace App\Facade\Resource;

use App\Entity\Structure;
use Bundles\ApiBundle\Annotation as Api;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class StructureResourceFacade extends ResourceFacade
{
    public function __construct()
    {
        $this->type = self::TYPE_STRUCTURE;
    }

    public static function getExample(Api\Facade $decorates = null) : FacadeInterface
    {
        $facade = new self;

        $facade->externalId = 'demo-structure';
        $facade->label      = 'Paris';

        return $facade;
    }

    public static function createFromStructure(Structure $structure) : self
    {
        $facade = new self;

        $facade->setExternalId($structure->getExternalId());
        $facade->setLabel($structure->getDisplayName());

        return $facade;
    }
}
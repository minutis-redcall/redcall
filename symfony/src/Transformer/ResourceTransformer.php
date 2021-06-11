<?php

namespace App\Transformer;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Facade\Generic\ResourceFacade;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class ResourceTransformer extends BaseTransformer
{
    public function expose($object) : ?FacadeInterface
    {
        $facade = new ResourceFacade();

        switch (get_class($object)) {
            case Volunteer::class:
                /** @var Volunteer $object */
                $facade->setType(ResourceFacade::TYPE_VOLUNTEER);
                $facade->setExternalId($object->getExternalId());
                $facade->setLabel($object->getDisplayName());
                break;
            case Structure::class:
                /** @var Structure $object */
                $facade->setType(ResourceFacade::TYPE_STRUCTURE);
                $facade->setExternalId($object->getExternalId());
                $facade->setLabel($object->getDisplayName());
                break;
            case Badge::class:
                /** @var Badge $object */
                $facade->setType(ResourceFacade::TYPE_BADGE);
                $facade->setExternalId($object->getExternalId());
                $facade->setLabel($object->getFullName());
                break;
            case Category::class:
                /** @var Category $object */
                $facade->setType(ResourceFacade::TYPE_CATEGORY);
                $facade->setExternalId($object->getExternalId());
                $facade->setLabel($object->getName());
                break;
            default:
                throw new \LogicException('Resource not supported');
        }

        return $facade;
    }
}
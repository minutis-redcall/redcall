<?php

namespace App\Transformer;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\Structure;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Facade\Resource\BadgeResourceFacade;
use App\Facade\Resource\CategoryResourceFacade;
use App\Facade\Resource\ResourceFacade;
use App\Facade\Resource\StructureResourceFacade;
use App\Facade\Resource\UserResourceFacade;
use App\Facade\Resource\VolunteerResourceFacade;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class ResourceTransformer extends BaseTransformer
{
    /**
     * @param Volunteer|Structure|Badge|Category $object
     *
     * @return ResourceFacade|null
     */
    public function expose($object) : ?FacadeInterface
    {
        if (!$object) {
            return null;
        } elseif ($object instanceof Volunteer) {
            $facade = new VolunteerResourceFacade();
            $facade->setExternalId($object->getExternalId());
            $facade->setLabel($object->getDisplayName());
        } elseif ($object instanceof Structure) {
            $facade = new StructureResourceFacade();
            $facade->setExternalId($object->getExternalId());
            $facade->setLabel($object->getDisplayName());
        } elseif ($object instanceof Badge) {
            $facade = new BadgeResourceFacade();
            $facade->setExternalId($object->getExternalId());
            $facade->setLabel($object->getFullName());
        } elseif ($object instanceof Category) {
            $facade = new CategoryResourceFacade();
            $facade->setExternalId($object->getExternalId());
            $facade->setLabel($object->getName());
        } elseif ($object instanceof User) {
            $facade = new UserResourceFacade();
            $facade->setExternalId($object->getUserIdentifier());
            $facade->setLabel($object->getExternalId() ?? $object->getUserIdentifier());
        } else {
            throw new \LogicException(sprintf('Resource of type "%s" is not supported', get_class($object)));
        }

        return $facade;
    }
}
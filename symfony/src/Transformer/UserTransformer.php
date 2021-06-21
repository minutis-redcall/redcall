<?php

namespace App\Transformer;

use App\Entity\Structure;
use App\Entity\User;
use App\Facade\Generic\ResourceFacade;
use App\Facade\User\UserFacade;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class UserTransformer extends BaseTransformer
{
    /**
     * @param User $object
     *
     * @return UserFacade
     */
    public function expose($object) : ?FacadeInterface
    {
        if (!$object) {
            return null;
        }

        $facade = new UserFacade();

        $facade->setIdentifier($object->getUserIdentifier());
        $facade->setVerified($object->isVerified());
        $facade->setTrusted($object->isTrusted());
        $facade->setDeveloper($object->isDeveloper());
        $facade->setAdministrator($object->isAdmin());
        $facade->setRoot($object->isRoot());

        if ($object->getVolunteer()) {
            $resource = new ResourceFacade();
            $resource->setType(ResourceFacade::TYPE_VOLUNTEER);
            $resource->setExternalId($object->getVolunteer()->getExternalId());
            $resource->setLabel($object->getVolunteer()->getDisplayName());
            $facade->setVolunteer($resource);
        }

        $structures = [];
        foreach ($object->getStructures() as $structure) {
            /** @var Structure $structure */
            $resource = new ResourceFacade();
            $resource->setType(ResourceFacade::TYPE_STRUCTURE);
            $resource->setExternalId($structure->getExternalId());
            $resource->setLabel($structure->getDisplayName());
            $structures[] = $resource;
        }
        $facade->setStructures($structures);

        return $facade;
    }
}
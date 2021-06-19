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
    public function expose($object) : ?FacadeInterface
    {
        /** @var User $user */
        $user = $object;

        $facade = new UserFacade();

        $facade->setIdentifier($user->getUserIdentifier());
        $facade->setVerified($user->isVerified());
        $facade->setTrusted($user->isTrusted());
        $facade->setDeveloper($user->isDeveloper());
        $facade->setAdministrator($user->isAdmin());
        $facade->setRoot($user->isRoot());

        if ($user->getVolunteer()) {
            $resource = new ResourceFacade();
            $resource->setType(ResourceFacade::TYPE_VOLUNTEER);
            $resource->setExternalId($user->getVolunteer()->getExternalId());
            $resource->setLabel($user->getVolunteer()->getDisplayName());
            $facade->setVolunteer($resource);
        }

        $structures = [];
        foreach ($user->getStructures() as $structure) {
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
<?php

namespace App\Transformer;

use App\Entity\Structure;
use App\Entity\User;
use App\Facade\Resource\StructureResourceFacade;
use App\Facade\Resource\VolunteerResourceFacade;
use App\Facade\User\UserFacade;
use App\Facade\User\UserReadFacade;
use App\Security\Helper\Security;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class UserTransformer extends BaseTransformer
{
    public static function getSubscribedServices()
    {
        return [
            Security::class,
        ];
    }

    /**
     * @param User $object
     *
     * @return UserReadFacade
     */
    public function expose($object) : ?FacadeInterface
    {
        if (!$object) {
            return null;
        }

        $facade = new UserReadFacade();

        $facade->setIdentifier($object->getUserIdentifier());
        $facade->setVerified($object->isVerified());
        $facade->setTrusted($object->isTrusted());
        $facade->setDeveloper($object->isDeveloper());
        $facade->setAdministrator($object->isAdmin());
        $facade->setRoot($object->isRoot());

        if ($object->getVolunteer()) {
            $resource = new VolunteerResourceFacade();
            $resource->setExternalId($object->getVolunteer()->getExternalId());
            $resource->setLabel($object->getVolunteer()->getDisplayName());
            $facade->setVolunteer($resource);
        }

        $structures = [];
        foreach ($object->getStructures() as $structure) {
            /** @var Structure $structure */
            $resource = new StructureResourceFacade();
            $resource->setExternalId($structure->getExternalId());
            $resource->setLabel($structure->getDisplayName());
            $structures[] = $resource;
        }
        $facade->setStructures($structures);

        return $facade;
    }

    /**
     * @param UserFacade $facade
     * @param User|null  $object
     *
     * @return User
     */
    public function reconstruct(FacadeInterface $facade, $object = null)
    {
        $user = $object;
        if (null === $object) {
            $user = new User();
            $user->setPlatform($this->getSecurity()->getPlatform());
        }

        if (null !== $facade->getIdentifier()) {
            $user->setUsername($facade->getIdentifier());
        }

        if (null !== $facade->isVerified()) {
            $user->setIsVerified($facade->isVerified());
        }

        if (null !== $facade->isTrusted()) {
            $user->setIsTrusted($facade->isTrusted());
        }

        if (null != $facade->isDeveloper()) {
            $user->setIsDeveloper($facade->isDeveloper());
        }

        if (null !== $facade->isAdministrator()) {
            $user->setIsAdmin($facade->isAdministrator());
        }

        if (null !== $facade->isRoot()) {
            $user->setIsRoot($facade->isRoot());
        }

        return $user;
    }

    private function getSecurity() : Security
    {
        return $this->get(Security::class);
    }
}
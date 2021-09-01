<?php

namespace App\Transformer;

use App\Entity\Structure;
use App\Entity\User;
use App\Facade\Resource\StructureResourceFacade;
use App\Facade\Resource\VolunteerResourceFacade;
use App\Facade\User\UserFacade;
use App\Facade\User\UserReadFacade;
use App\Manager\PlatformConfigManager;
use App\Manager\VolunteerManager;
use App\Security\Helper\Security;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class UserTransformer extends BaseTransformer
{
    public static function getSubscribedServices()
    {
        return [
            Security::class,
            VolunteerManager::class,
            PlatformConfigManager::class,
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

        if ($object->getExternalId()) {
            $facade->setVolunteerExternalId($object->getExternalId());
        }

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

        $facade->setLocked($object->isLocked());

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

            $platform = $this->getPlatformConfigManager()->getPlaform($this->getSecurity()->getPlatform());
            $user->setLocale($platform->getDefaultLanguage()->getLocale());
            $user->setTimezone($platform->getTimezone());
            $user->setPassword('invalid hash');
        }

        if (null !== $facade->getIdentifier()) {
            $user->setUsername($facade->getIdentifier());
        }

        if (false === $facade->getVolunteerExternalId()) {
            $object->setVolunteer(null);
        } elseif (null !== $facade->getVolunteerExternalId()) {
            $volunteer = $this->getVolunteerManager()->findOneByExternalId(
                $this->getSecurity()->getPlatform(),
                $facade->getVolunteerExternalId()
            );

            $object->setVolunteer($volunteer);
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

    private function getVolunteerManager() : VolunteerManager
    {
        return $this->get(VolunteerManager::class);
    }

    private function getPlatformConfigManager() : PlatformConfigManager
    {
        return $this->get(PlatformConfigManager::class);
    }
}
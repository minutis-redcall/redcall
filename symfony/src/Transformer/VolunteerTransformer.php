<?php

namespace App\Transformer;

use App\Entity\Volunteer;
use App\Facade\Volunteer\VolunteerFacade;
use App\Facade\Volunteer\VolunteerReadFacade;
use App\Manager\BadgeManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Security\Helper\Security;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class VolunteerTransformer extends BaseTransformer
{
    public static function getSubscribedServices()
    {
        return [
            Security::class,
            ResourceTransformer::class,
            PhoneTransformer::class,
            BadgeManager::class,
            UserManager::class,
            StructureManager::class,
            VolunteerManager::class,
        ];
    }

    /**
     * @param Volunteer|null $object
     *
     * @return VolunteerReadFacade|null
     */
    public function expose($object) : ?FacadeInterface
    {
        if (!$object) {
            return null;
        }

        $facade = new VolunteerReadFacade();
        $facade->setExternalId($object->getExternalId());
        $facade->setFirstName($object->getFirstName());
        $facade->setLastName($object->getLastName());
        $facade->setBirthday($object->getBirthday() ? $object->getBirthday()->format('Y-m-d') : null);
        $facade->setOptoutUntil($object->getOptoutUntil() ? $object->getOptoutUntil()->format('Y-m-d') : null);
        $facade->setEmail($object->getEmail());
        $facade->setEmailOptin($object->isEmailOptin());
        $facade->setEmailLocked($object->isEmailLocked());
        $facade->setPhoneOptin($object->isPhoneNumberOptin());
        $facade->setPhoneLocked($object->isPhoneNumberLocked());
        $facade->setLocked($object->isLocked());
        $facade->setEnabled($object->isEnabled());

        foreach ($object->getStructures() as $structure) {
            $facade->addStructure(
                $this->getResourceTransformer()->expose($structure)
            );
        }

        foreach ($object->getPhones() as $phone) {
            $facade->addPhone(
                $this->getPhoneTransformer()->expose($phone)
            );
        }

        foreach ($object->getBadges() as $badge) {
            $facade->addBadge(
                $this->getResourceTransformer()->expose($badge)
            );
        }

        if ($object->getUser()) {
            $facade->setUser(
                $this->getResourceTransformer()->expose($object->getUser())
            );
        }

        return $facade;
    }

    /**
     * @param VolunteerFacade $facade
     * @param Volunteer|null  $object
     *
     * @return Volunteer
     */
    public function reconstruct(FacadeInterface $facade, $object = null)
    {
        $volunteer = $object;
        if (null === $object) {
            $volunteer = new Volunteer();
            $volunteer->setPlatform($this->getSecurity()->getPlatform());
        }

        if (null !== $facade->getExternalId()) {
            $volunteer->setExternalId($facade->getExternalId());
        }

        if (null !== $facade->getFirstName()) {
            $volunteer->setFirstName($facade->getFirstName());
        }

        if (null !== $facade->getLastName()) {
            $volunteer->setLastName($facade->getLastName());
        }

        if (null !== $facade->getBirthday()) {
            $volunteer->setBirthday(new \DateTime(sprintf('%s 00:00:00', $facade->getBirthday())));
        }

        if (null !== $facade->getOptoutUntil()) {
            $volunteer->setOptoutUntil(new \DateTime(sprintf('%s 00:00:00', $facade->getOptoutUntil())));
        }

        if (null !== $facade->getEmail()) {
            $volunteer->setEmail($facade->getEmail());
        }

        if (null !== $facade->getEmailOptin()) {
            $volunteer->setEmailOptin($facade->getEmailOptin());
        }

        if (null !== $facade->getEmailLocked()) {
            $volunteer->setEmailLocked($facade->getEmailLocked());
        }

        if (null !== $facade->getPhoneOptin()) {
            $volunteer->setPhoneNumberOptin($facade->getPhoneOptin());
        }

        if (null !== $facade->getPhoneLocked()) {
            $volunteer->setPhoneNumberLocked($facade->getPhoneLocked());
        }

        return $volunteer;
    }

    private function getResourceTransformer() : ResourceTransformer
    {
        return $this->get(ResourceTransformer::class);
    }

    private function getSecurity() : Security
    {
        return $this->get(Security::class);
    }

    private function getPhoneTransformer() : PhoneTransformer
    {
        return $this->get(PhoneTransformer::class);
    }

    private function getBadgeManager() : BadgeManager
    {
        return $this->get(BadgeManager::class);
    }

    private function getUserManager() : UserManager
    {
        return $this->get(UserManager::class);
    }

    private function getStructureManager() : StructureManager
    {
        return $this->get(StructureManager::class);
    }

    private function getVolunteerManager() : VolunteerManager
    {
        return $this->get(VolunteerManager::class);
    }
}
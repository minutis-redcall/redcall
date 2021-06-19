<?php

namespace App\Transformer;

use App\Entity\Volunteer;
use App\Facade\Volunteer\VolunteerReadFacade;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class VolunteerTransformer extends BaseTransformer
{
    public static function getSubscribedServices()
    {
        return [
            ResourceTransformer::class,
            PhoneTransformer::class,
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

    private function getResourceTransformer() : ResourceTransformer
    {
        return $this->get(ResourceTransformer::class);
    }

    private function getPhoneTransformer() : PhoneTransformer
    {
        return $this->get(PhoneTransformer::class);
    }
}
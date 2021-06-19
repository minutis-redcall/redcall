<?php

namespace App\Transformer;

use App\Entity\Structure;
use App\Facade\Structure\StructureReadFacade;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class StructureTransformer extends BaseTransformer
{
    public static function getSubscribedServices()
    {
        return [
            VolunteerManager::class,
            UserManager::class,
            ResourceTransformer::class,
        ];
    }

    /**
     * @param Structure|null $object
     *
     * @return StructureReadFacade|null
     */
    public function expose($object) : ?FacadeInterface
    {
        $facade = new StructureReadFacade();

        $facade->setExternalId($object->getExternalId());
        $facade->setName($object->getName());
        $facade->setPresidentExternalId($object->getPresident());
        $facade->setLocked($object->isLocked());
        $facade->setEnabled($object->isEnabled());

        $facade->setVolunteersCount(
            $this->getVolunteerManager()->getVolunteerCountInStructure($object)
        );

        $facade->setUsersCount(
            $this->getUserManager()->getUserCountInStructure($object)
        );

        $facade->setParentStructure(
            $object->getParentStructure() ? $this->getResourceTransformer()->expose($object->getParentStructure()) : null
        );

        foreach ($object->getChildrenStructures() as $child) {
            $facade->addChildrenStructure(
                $this->getResourceTransformer()->expose($child)
            );
        }

        return $facade;
    }

    private function getVolunteerManager() : VolunteerManager
    {
        return $this->get(VolunteerManager::class);
    }

    private function getUserManager() : UserManager
    {
        return $this->get(UserManager::class);
    }

    private function getResourceTransformer() : ResourceTransformer
    {
        return $this->get(ResourceTransformer::class);
    }
}
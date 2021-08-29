<?php

namespace App\Transformer;

use App\Entity\Structure;
use App\Facade\Structure\StructureFacade;
use App\Facade\Structure\StructureReadFacade;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Security\Helper\Security;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class StructureTransformer extends BaseTransformer
{
    public static function getSubscribedServices()
    {
        return [
            Security::class,
            VolunteerManager::class,
            UserManager::class,
            StructureManager::class,
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
        $facade->setParentExternalId($object->getParentStructure() ? $object->getParentStructure()->getExternalId() : null);
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

    /**
     * @param StructureFacade $facade
     * @param Structure|null  $object
     *
     * @return Structure
     */
    public function reconstruct(FacadeInterface $facade, $object = null)
    {
        $structure = $object;
        if (null === $object) {
            $structure = new Structure();
            $structure->setPlatform($this->getSecurity()->getPlatform());
        }

        if (null !== $facade->getExternalId()) {
            $structure->setExternalId($facade->getExternalId());
        }

        if (null !== $facade->getParentExternalId()) {
            $parent = $this->getStructureManager()->findOneByExternalId(
                $this->getSecurity()->getPlatform(),
                $facade->getParentExternalId()
            );

            $structure->setParentStructure($parent);
        }

        if (null !== $facade->getName()) {
            $structure->setName($facade->getName());
        }

        if (null !== $facade->getPresidentExternalId()) {
            $volunteer = $this->getVolunteerManager()->findOneByExternalId(
                $this->getSecurity()->getPlatform(),
                $facade->getExternalId()
            );

            $structure->setPresident($volunteer ? $volunteer->getExternalId() : null);
        }

        if (null !== $facade->getLocked()) {
            $structure->setLocked($facade->getLocked());
        }

        if (null !== $facade->getEnabled()) {
            $structure->setEnabled($facade->getEnabled());
        }

        return $structure;
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

    private function getStructureManager() : StructureManager
    {
        return $this->get(StructureManager::class);
    }

    private function getSecurity() : Security
    {
        return $this->get(Security::class);
    }
}
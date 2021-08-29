<?php

namespace App\Transformer;

use App\Entity\Badge;
use App\Facade\Badge\BadgeFacade;
use App\Facade\Badge\BadgeReadFacade;
use App\Manager\BadgeManager;
use App\Manager\CategoryManager;
use App\Manager\VolunteerManager;
use App\Security\Helper\Security;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class BadgeTransformer extends BaseTransformer
{
    public static function getSubscribedServices()
    {
        return [
            Security::class,
            CategoryManager::class,
            BadgeManager::class,
            VolunteerManager::class,
            ResourceTransformer::class,
        ];
    }

    /**
     * @param Badge|null $object
     *
     * @return BadgeReadFacade|null
     */
    public function expose($object) : ?FacadeInterface
    {
        if (!$object) {
            return null;
        }

        $facade = new BadgeReadFacade();
        $facade->setExternalId($object->getExternalId());

        if ($object->getCategory()) {
            $facade->setCategoryExternalId($object->getCategory()->getExternalId());
        }

        $facade->setName($object->getName());
        $facade->setDescription($object->getDescription());
        $facade->setVisibility($object->getVisibility());
        $facade->setRenderingPriority($object->getRenderingPriority());
        $facade->setTriggeringPriority($object->getTriggeringPriority());

        if ($object->getParent()) {
            $facade->setCoveredByExternalId($object->getParent()->getExternalId());
        }

        if ($object->getSynonym()) {
            $facade->setReplacedByExternalId($object->getSynonym()->getExternalId());
        }

        $facade->setEnabled($object->isEnabled());

        $facade->setLocked($object->isLocked());
        $facade->setCategory(
            $this->getResourceTransformer()->expose($object->getCategory())
        );

        foreach ($object->getCoveringBadges() as $badge) {
            $facade->addCovers(
                $this->getResourceTransformer()->expose($badge)
            );
        }

        foreach ($object->getCoveredBadges() as $badge) {
            $facade->addCoveredBy(
                $this->getResourceTransformer()->expose($badge)
            );
        }

        $facade->setReplacedBy(
            $this->getResourceTransformer()->expose($object->getSynonym())
        );

        foreach ($object->getSynonyms() as $synonym) {
            $facade->addReplaces(
                $this->getResourceTransformer()->expose($synonym)
            );
        }

        $facade->setPeopleCount(
            $this->getVolunteerManager()->getVolunteerCountHavingBadgesQueryBuilder([$object->getId()])
        );

        return $facade;
    }

    /**
     * @param BadgeFacade $facade
     * @param Badge|null  $object
     *
     * @return Badge
     */
    public function reconstruct(FacadeInterface $facade, $object = null)
    {
        if (!$object) {
            $object = new Badge();
            $object->setPlatform($this->getSecurity()->getPlatform());
        }

        if (null !== $facade->getExternalId()) {
            $object->setExternalId($facade->getExternalId());
        }

        if (null !== $facade->getCategoryExternalId()) {
            $category = $this->getCategoryManager()->findOneByExternalId(
                $this->getSecurity()->getPlatform(),
                $facade->getCategoryExternalId()
            );

            if ($category) {
                $object->setCategory($category);
            }
        }

        if (null !== $facade->getName()) {
            $object->setName($facade->getName());
        }

        if (null !== $facade->getDescription()) {
            $object->setDescription($facade->getDescription());
        }

        if (null !== $facade->getVisibility()) {
            $object->setVisibility($facade->getVisibility());
        }

        if (null !== $facade->getRenderingPriority()) {
            $object->setRenderingPriority($facade->getRenderingPriority());
        }

        if (null !== $facade->getTriggeringPriority()) {
            $object->setTriggeringPriority($facade->getTriggeringPriority());
        }

        if (null !== $facade->getCoveredByExternalId()) {
            $parent = $this->getBadgeManager()->findOneByExternalId(
                $this->getSecurity()->getPlatform(),
                $facade->getCoveredByExternalId()
            );

            if ($parent) {
                $object->setParent($parent);
            }
        }

        if (null !== $facade->getReplacedByExternalId()) {
            $synonym = $this->getBadgeManager()->findOneByExternalId(
                $this->getSecurity()->getPlatform(),
                $facade->getReplacedByExternalId()
            );

            if ($synonym) {
                $object->setSynonym($synonym);
            }
        }

        if (null !== $facade->getLocked()) {
            $object->setLocked($facade->getLocked());
        }

        if (null !== $facade->getEnabled()) {
            $object->setEnabled($facade->getEnabled());
        }

        return $object;
    }

    private function getSecurity() : Security
    {
        return $this->get(Security::class);
    }

    private function getCategoryManager() : CategoryManager
    {
        return $this->get(CategoryManager::class);
    }

    private function getBadgeManager() : BadgeManager
    {
        return $this->get(BadgeManager::class);
    }

    private function getVolunteerManager() : VolunteerManager
    {
        return $this->get(VolunteerManager::class);
    }

    private function getResourceTransformer() : ResourceTransformer
    {
        return $this->get(ResourceTransformer::class);
    }
}
<?php

namespace App\Transformer\Admin;

use App\Entity\Badge;
use App\Facade\Admin\Badge\BadgeFacade;
use App\Facade\Admin\Badge\BadgeReadFacade;
use App\Manager\VolunteerManager;
use App\Security\Helper\Security;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class BadgeTransformer extends BaseTransformer
{
    public static function getSubscribedServices()
    {
        return [
            CategoryTransformer::class,
            Security::class,
            VolunteerManager::class,
        ];
    }

    public function expose($object) : ?FacadeInterface
    {
        /** @var Badge $badge */
        $badge = $object;

        if (!$badge) {
            return null;
        }

        $facade = new BadgeReadFacade();
        $facade
            ->setExternalId($badge->getExternalId())
            ->setName($badge->getName())
            ->setDescription($badge->getDescription())
            ->setVisibility($badge->getVisibility())
            ->setRenderingPriority($badge->getRenderingPriority())
            ->setTriggeringPriority($badge->getTriggeringPriority())
            ->setEnabled($badge->isEnabled())
            ->setLocked($badge->isLocked())
            ->setCategory(
                $this->getCategoryTransformer()->expose($badge->getCategory())
            )
            ->setCoveredBy(
                count($badge->getCoveringBadges())
            )
            ->setCoversCount(
                count($badge->getCoveredBadges())
            )
            ->setReplacedBy(
                $this->expose($badge->getSynonym())
            )
            ->setReplacesCount(
                count($badge->getSynonyms())
            )
            ->setPeopleCount(
                $this->getVolunteerManager()->getVolunteerCountHavingBadgesQueryBuilder([$badge->getId()])
            );

        return $facade;
    }

    public function reconstruct(FacadeInterface $facade, $object = null)
    {
        /** @var BadgeFacade $facade */
        $badge = $object;
        if (!$badge) {
            $badge = new Badge();
            $badge->setPlatform($this->getSecurity()->getPlatform());
        }

        if (null !== $facade->getExternalId()) {
            $badge->setExternalId($facade->getExternalId());
        }

        if (null !== $facade->getName()) {
            $badge->setName($facade->getName());
        }

        if (null !== $facade->getDescription()) {
            $badge->setDescription($facade->getDescription());
        }

        if (null !== $facade->getVisibility()) {
            $badge->setVisibility($facade->getVisibility());
        }

        if (null !== $facade->getRenderingPriority()) {
            $badge->setRenderingPriority($facade->getRenderingPriority());
        }

        if (null !== $facade->getTriggeringPriority()) {
            $badge->setTriggeringPriority($facade->getTriggeringPriority());
        }

        if (null !== $facade->getLocked()) {
            $badge->setLocked($facade->getLocked());
        }

        if (null !== $facade->getEnabled()) {
            $badge->setEnabled($facade->getEnabled());
        }

        return $badge;
    }

    private function getSecurity() : Security
    {
        return $this->get(Security::class);
    }

    private function getCategoryTransformer() : CategoryTransformer
    {
        return $this->get(CategoryTransformer::class);
    }

    private function getVolunteerManager() : VolunteerManager
    {
        return $this->get(VolunteerManager::class);
    }
}
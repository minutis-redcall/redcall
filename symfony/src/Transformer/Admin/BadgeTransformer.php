<?php

namespace App\Transformer\Admin;

use App\Entity\Badge;
use App\Facade\Admin\Badge\BadgeReadFacade;
use App\Manager\VolunteerManager;
use Bundles\ApiBundle\Base\BaseTransformer;
use Bundles\ApiBundle\Contracts\FacadeInterface;

class BadgeTransformer extends BaseTransformer
{
    public static function getSubscribedServices()
    {
        return [
            CategoryTransformer::class,
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
        /** @var BadgeWriteFacade $facade */

        //$badge = $object ?? new BadgeWriteFacade();

        // ...

        // return $badge;
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
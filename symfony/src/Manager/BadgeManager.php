<?php

namespace App\Manager;

use App\Entity\Badge;
use App\Entity\Category;
use App\Entity\User;
use App\Entity\Volunteer;
use App\Repository\BadgeRepository;
use App\Security\Helper\Security;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class BadgeManager
{
    /**
     * @var BadgeRepository
     */
    private $badgeRepository;

    /**
     * @var Security
     */
    private $security;

    public function __construct(BadgeRepository $badgeRepository, Security $security)
    {
        $this->badgeRepository = $badgeRepository;
        $this->security        = $security;
    }

    public function find(int $id) : ?Badge
    {
        return $this->badgeRepository->find($id);
    }

    public function findOneByExternalId(string $platform, string $externalId) : ?Badge
    {
        return $this->badgeRepository->findOneByExternalId($platform, $externalId);
    }

    public function save(Badge $badge)
    {
        $this->badgeRepository->save($badge);
    }

    public function getSearchInBadgesQueryBuilder(string $platform, ?string $criteria, bool $onlyEnabled)
    {
        return $this->badgeRepository->getSearchInBadgesQueryBuilder($platform, $criteria, $onlyEnabled);
    }

    public function searchForCompletion(string $platform, ?string $criteria, int $limit = 0) : array
    {
        return $this->badgeRepository->searchForCompletion($platform, $criteria, $limit);
    }

    public function searchNonVisibleUsableBadge(string $platform, ?string $criteria, int $limit = 0) : array
    {
        return $this->badgeRepository->searchNonVisibleUsableBadge($platform, $criteria, $limit);
    }

    public function getNonVisibleUsableBadgesList(string $platform, array $ids)
    {
        return $this->badgeRepository->getNonVisibleUsableBadgesList($platform, $ids);
    }

    public function remove(Badge $badge)
    {
        $this->badgeRepository->remove($badge);
    }

    public function getVolunteerCountInSearch(Pagerfanta $pager) : array
    {
        $ids = [];
        foreach ($pager->getIterator() as $badge) {
            /** @var Badge $badge */
            $ids[] = $badge->getId();
        }

        return $this->getVolunteerCountInBadgeList($ids);
    }

    public function getVolunteerCountInBadgeList(array $ids) : array
    {
        return $this->badgeRepository->getVolunteerCountInBadgeList($ids);
    }

    public function getPublicBadgesQueryBuilder(string $platform) : QueryBuilder
    {
        return $this->badgeRepository->getPublicBadgesQueryBuilder($platform);
    }

    public function getPublicBadges(string $platform) : array
    {
        return $this
            ->getPublicBadgesQueryBuilder($platform)
            ->getQuery()
            ->getResult();
    }

    public function getCustomOrPublicBadges() : array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user->getFavoriteBadges()->count()) {
            $publicBadges = $user->getSortedFavoriteBadges();
        } else {
            $publicBadges = $this->getPublicBadges($this->security->getPlatform());
        }

        return $publicBadges;
    }

    public function getBadgesInCategoryQueryBuilder(string $platform, Category $category) : QueryBuilder
    {
        return $this->badgeRepository->getBadgesInCategoryQueryBuilder($platform, $category);
    }

    public function searchForVolunteerQueryBuilder(Volunteer $volunteer, ?string $criteria) : QueryBuilder
    {
        return $this->badgeRepository->searchForVolunteerQueryBuilder(
            $volunteer->getPlatform(),
            $volunteer,
            $criteria
        );
    }
}
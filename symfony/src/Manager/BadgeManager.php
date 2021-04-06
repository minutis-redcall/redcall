<?php

namespace App\Manager;

use App\Entity\Badge;
use App\Entity\Category;
use App\Repository\BadgeRepository;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Pagerfanta;

class BadgeManager
{
    /**
     * @var BadgeRepository
     */
    private $badgeRepository;

    /**
     * @param BadgeRepository $badgeRepository
     */
    public function __construct(BadgeRepository $badgeRepository)
    {
        $this->badgeRepository = $badgeRepository;
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

    public function getSearchInPublicBadgesQueryBuilder(?string $criteria, bool $onlyEnabled)
    {
        return $this->badgeRepository->getSearchInBadgesQueryBuilder($criteria, $onlyEnabled);
    }

    public function search(?string $criteria, int $limit = 0) : array
    {
        return $this->badgeRepository->search($criteria, $limit);
    }

    public function searchForCompletion(?string $criteria, int $limit = 0) : array
    {
        return $this->badgeRepository->searchForCompletion($criteria, $limit);
    }

    public function searchNonVisibleUsableBadge(?string $criteria, int $limit = 0) : array
    {
        return $this->badgeRepository->searchNonVisibleUsableBadge($criteria, $limit);
    }

    public function getNonVisibleUsableBadgesList(array $ids)
    {
        return $this->badgeRepository->getNonVisibleUsableBadgesList($ids);
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

    public function getPublicBadgesQueryBuilder() : QueryBuilder
    {
        return $this->badgeRepository->getPublicBadgesQueryBuilder();
    }

    public function getPublicBadges() : array
    {
        return $this
            ->getPublicBadgesQueryBuilder()
            ->getQuery()
            ->getResult();
    }

    public function getBadgesInCategoryQueryBuilder(Category $category) : QueryBuilder
    {
        return $this->badgeRepository->getBadgesInCategoryQueryBuilder($category);
    }
}
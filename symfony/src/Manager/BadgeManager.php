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

    public function findOneByExternalId(string $externalId) : ?Badge
    {
        return $this->badgeRepository->findOneByExternalId($externalId);
    }

    public function findOneByName(string $name) : ?Badge
    {
        return $this->badgeRepository->findOneByName($name);
    }

    public function save(Badge $badge)
    {
        $this->badgeRepository->save($badge);
    }

    public function getSearchInBadgesQueryBuilder(?string $criteria, bool $onlyEnabled)
    {
        return $this->badgeRepository->getSearchInBadgesQueryBuilder($criteria, $onlyEnabled);
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

    public function getCustomOrPublicBadges() : array
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if ($user->getFavoriteBadges()->count()) {
            $publicBadges = $user->getSortedFavoriteBadges();
        } else {
            $publicBadges = $this->getPublicBadges();
        }

        return $publicBadges;
    }

    public function getBadgesInCategoryQueryBuilder(Category $category) : QueryBuilder
    {
        return $this->badgeRepository->getBadgesInCategoryQueryBuilder($category);
    }

    public function searchForVolunteerQueryBuilder(Volunteer $volunteer, ?string $criteria) : QueryBuilder
    {
        return $this->badgeRepository->searchForVolunteerQueryBuilder(
            $volunteer,
            $criteria
        );
    }
}
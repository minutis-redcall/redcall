<?php

namespace App\Manager;

use App\Entity\Badge;
use App\Repository\BadgeRepository;

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

    public function findOneByExternalId(string $externalId) : ?Badge
    {
        return $this->badgeRepository->findOneByExternalId($externalId);
    }

    public function save(Badge $badge)
    {
        $this->badgeRepository->save($badge);
    }

    public function getSearchInPublicBadgesQueryBuilder(?string $criteria)
    {
        return $this->badgeRepository->getSearchInPublicBadgesQueryBuilder($criteria);
    }

    public function search(?string $criteria, int $limit = 0) : array
    {
        return $this->badgeRepository->search($criteria, $limit);
    }
}
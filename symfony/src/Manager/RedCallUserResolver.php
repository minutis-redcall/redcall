<?php

namespace App\Manager;

use App\Entity\User;
use App\Entity\Volunteer;
use App\Repository\UserRepository;
use App\Repository\VolunteerRepository;

/**
 * Resolves the RedCall operator (trusted User) that shares a volunteer's NIVOL.
 *
 * Since the User <-> Volunteer entity link was removed, the two are related
 * only by sharing an external id. Templates and list views ask this question
 * a lot (the "is also a RedCall user" indicators), so this service keeps a
 * per-request identity map and exposes a batch preloader to avoid an N+1 of
 * one query per rendered volunteer card.
 */
class RedCallUserResolver
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var VolunteerRepository
     */
    private $volunteerRepository;

    /**
     * @var array<string, User|null>
     */
    private $cache = [];

    /**
     * @var array<string, Volunteer|null>
     */
    private $volunteerCache = [];

    public function __construct(UserRepository $userRepository, VolunteerRepository $volunteerRepository)
    {
        $this->userRepository      = $userRepository;
        $this->volunteerRepository = $volunteerRepository;
    }

    /**
     * The directory record (Volunteer) sharing this operator's NIVOL, if any.
     * Inverse of {@see resolve()} — used by views that show "the operator's own
     * volunteer record" (personal space link, structure user cards, ...).
     */
    public function volunteerOf(?User $user) : ?Volunteer
    {
        if (!$user || !$user->getExternalId()) {
            return null;
        }

        $externalId = $user->getExternalId();
        if (!array_key_exists($externalId, $this->volunteerCache)) {
            $this->volunteerCache[$externalId] = $this->volunteerRepository->findOneByExternalId($externalId);
        }

        return $this->volunteerCache[$externalId];
    }

    /**
     * Warms the cache for a batch of volunteers in a single query. Call this
     * from a controller before rendering a list to avoid per-row lookups.
     *
     * @param iterable<Volunteer> $volunteers
     */
    public function preload(iterable $volunteers) : void
    {
        $externalIds = [];
        foreach ($volunteers as $volunteer) {
            $externalId = $volunteer->getExternalId();
            if ($externalId && !array_key_exists($externalId, $this->cache)) {
                $externalIds[] = $externalId;
            }
        }

        if (!$externalIds) {
            return;
        }

        $found = $this->userRepository->findTrustedByExternalIds($externalIds);
        foreach ($externalIds as $externalId) {
            $this->cache[$externalId] = $found[$externalId] ?? null;
        }
    }

    public function resolve(?Volunteer $volunteer) : ?User
    {
        if (!$volunteer || !$volunteer->getExternalId()) {
            return null;
        }

        $externalId = $volunteer->getExternalId();
        if (!array_key_exists($externalId, $this->cache)) {
            $this->cache[$externalId] = $this->userRepository->findOneTrustedByExternalId($externalId);
        }

        return $this->cache[$externalId];
    }

    /**
     * Mirrors the old Volunteer::isUserEnabled(): a verified AND trusted user.
     */
    public function isEnabled(?Volunteer $volunteer) : bool
    {
        $user = $this->resolve($volunteer);

        return $user && $user->isVerified() && $user->isTrusted();
    }
}

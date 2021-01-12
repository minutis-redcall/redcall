<?php

namespace App\Manager;

use App\Entity\Badge;
use App\Entity\Volunteer;
use App\Model\Classification;
use Symfony\Component\Security\Core\Security;

class AudienceManager
{
    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(UserManager $userManager,
        VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        BadgeManager $badgeManager,
        Security $security)
    {
        $this->userManager      = $userManager;
        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->badgeManager     = $badgeManager;
        $this->security         = $security;
    }

    public function getVolunteerList(array $ids) : array
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $volunteers = $this->volunteerManager->getVolunteerList($ids);
        } else {
            $volunteers = $this->volunteerManager->getVolunteerListForCurrentUser($ids);
        }

        return array_map(function (Volunteer $volunteer) {
            return $volunteer->toSearchResults();
        }, $volunteers);
    }

    public function getBadgeList(array $ids) : array
    {
        $badges = $this->badgeManager->getNonVisibleUsableBadgesList($ids);

        return array_map(function (Badge $badge) {
            return $badge->toSearchResults();
        }, $badges);
    }

    public function classifyAudience(array $data) : Classification
    {
        $classification = new Classification();

        if (true === ($data['test_on_me'] ?? false)) {
            $classification->setReachable([
                $this->userManager->findForCurrentUser()->getVolunteer()->getId(),
            ]);

            return $classification;
        }

        if ($data['nivols']) {
            $classification->setInvalid(
                $this->volunteerManager->filterInvalidNivols($data['nivols'])
            );
        }

        $audience = $this->extractAudience($data);

        $classification->setDisabled(
            $this->volunteerManager->filterDisabled($audience)
        );

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $classification->setInaccessible(
                $this->volunteerManager->filterInaccessibles($audience)
            );
        }

        // Reducing the audience to potentially reachable volunteers
        $audience = array_diff($audience, $classification->getDisabled(), $classification->getInaccessible());
        $classification->setReachable($audience);

        // Adding more contextual information in order to help fix contact info
        $classification->setPhoneLandline(
            $this->volunteerManager->filterPhoneLandline($audience)
        );

        $classification->setPhoneMissing(
            $this->volunteerManager->filterPhoneMissing($audience)
        );

        $classification->setPhoneOptout(
            $this->volunteerManager->filterPhoneOptout($audience)
        );

        $classification->setEmailMissing(
            $this->volunteerManager->filterEmailMissing($audience)
        );

        $classification->setEmailOptout(
            $this->volunteerManager->filterEmailOptout($audience)
        );

        return $classification;
    }

    public function extractAudience(array $data) : array
    {
        $volunteerIds = array_merge(
            $data['volunteers'] ?? [],
            $data['nivols'] ? $this->volunteerManager->getIdsByNivols($data['nivols']) : []
        );

        $structureIds = $this->extractStructures($data);

        if ($data['badges_all'] ?? false) {
            $volunteerIds = array_merge(
                $volunteerIds,
                $this->volunteerManager->getVolunteerListInStructures($structureIds)
            );
        } else {
            $badgeIds = array_unique(array_merge($data['badges_ticked'] ?? [], $data['badges_searched'] ?? []));

            $volunteerIds = array_merge(
                $volunteerIds,
                $this->volunteerManager->getVolunteerListInStructuresHavingBadges($structureIds, $badgeIds)
            );
        }

        return array_filter(array_unique($volunteerIds));
    }

    public function extractStructures(array $data) : array
    {
        // This method is called twice when updating numbers:
        // - when we need to get the classification data
        // - when we need to get the badge counts for selected structures
        // So we cache results to prevent hitting the db twice
        static $cache = [];

        $hash = sha1(json_encode($data));
        if (array_key_exists($hash, $cache)) {
            return $cache[$hash];
        }

        $structureIds = $data['structures_local'] ?? [];
        if ($data['structures_global']) {
            $structureIds = array_merge(
                $structureIds,
                $this->structureManager->getDescendantStructures($data['structures_global'])
            );
        }

        $cache[$hash] = $structureIds;

        return $cache[$hash];
    }

    public function extractBadgeCounts(array $data, array $badgeList) : array
    {
        $structureIds = $this->extractStructures($data);

        $counts = [
            0 => $this->volunteerManager->getVolunteerCountInStructures($structureIds),
        ];

        foreach ($badgeList as $badge) {
            /** @var Badge $badge */
            $counts[$badge->getId()] = $this->volunteerManager->getVolunteerCountInStructuresHavingBadges($structureIds, [$badge->getId()]);
        }

        return $counts;
    }
}
<?php

namespace App\Manager;

use App\Entity\Badge;
use App\Entity\Volunteer;
use App\Model\Classification;
use App\Security\Helper\Security;

class AudienceManager
{
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
     * @var ExpirableManager
     */
    private $expirableManager;

    /**
     * @var Security
     */
    private $security;

    public function __construct(VolunteerManager $volunteerManager,
        StructureManager $structureManager,
        BadgeManager $badgeManager,
        ExpirableManager $expirableManager,
        Security $security)
    {
        $this->volunteerManager = $volunteerManager;
        $this->structureManager = $structureManager;
        $this->badgeManager     = $badgeManager;
        $this->expirableManager = $expirableManager;
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
            return $volunteer->toSearchResults($this->security->getUser());
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
                $this->security->getUser()->getVolunteer()->getId(),
            ]);

            return $classification;
        }

        if ($data['external_ids']) {
            $classification->setInvalid(
                $this->volunteerManager->filterInvalidExternalIds($data['external_ids'])
            );
        }

        $audience = $this->extractAudience($data);

        // Batch-fetch volunteer attributes + phone status in 2 queries (instead of 8-10)
        $batchData    = $this->volunteerManager->batchClassifyVolunteers($audience);
        $now          = new \DateTime();
        $disabled     = [];
        $optoutUntil  = [];
        $minors       = [];
        $emailMissing = [];
        $emailOptout  = [];
        $phoneOptout  = [];

        // Index volunteer data by ID
        foreach ($batchData['volunteers'] as $vol) {
            $id = (int) $vol['id'];
            if (!$vol['enabled']) {
                $disabled[] = $id;
            }
            if ($vol['enabled'] && $vol['optout_until'] && new \DateTime($vol['optout_until']) > $now) {
                $optoutUntil[] = $id;
            }
            if ($vol['minor']) {
                $minors[] = $id;
            }
            if ($vol['email'] === null && $vol['email_optin']) {
                $emailMissing[] = $id;
            }
            if ($vol['email'] !== null && !$vol['email_optin']) {
                $emailOptout[] = $id;
            }
            if (!$vol['phone_number_optin']) {
                $phoneOptout[] = $id;
            }
        }

        // Index phone data by volunteer ID
        $phoneByVolunteer = [];
        foreach ($batchData['phones'] as $phone) {
            $phoneByVolunteer[(int) $phone['volunteer_id']] = $phone;
        }

        // Phone classification
        $phoneMissing  = [];
        $phoneLandline = [];

        foreach ($batchData['volunteers'] as $vol) {
            $id = (int) $vol['id'];
            if (!$vol['phone_number_optin']) {
                continue; // Already in phoneOptout
            }

            $phoneInfo = $phoneByVolunteer[$id] ?? null;

            if (!$phoneInfo) {
                $phoneMissing[] = $id;
            } elseif ($phoneInfo['preferred_is_landline']) {
                $phoneLandline[] = $id;
            }
        }

        $classification->setDisabled($disabled);
        $classification->setOptoutUntil($optoutUntil);

        if (!$data['allow_minors']) {
            $classification->setExcludedMinors($minors);
        }

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $classification->setInaccessible(
                $this->volunteerManager->filterInaccessibles($audience)
            );
        }

        if ($data['excluded_volunteers']) {
            $classification->setExcluded($data['excluded_volunteers']);
        }

        // Reducing the audience to potentially reachable volunteers
        $audience = array_diff(
            $audience,
            $classification->getDisabled(),
            $classification->getInaccessible(),
            $classification->getExcluded(),
            $classification->getOptoutUntil(),
            $classification->getExcludedMinors(),
        );

        $classification->setReachable($audience);

        // Contextual info for reachable volunteers only
        $reachableSet = array_flip($audience);
        $classification->setPhoneLandline(array_values(array_filter($phoneLandline, function ($id) use ($reachableSet) { return isset($reachableSet[$id]); })));
        $classification->setPhoneMissing(array_values(array_filter($phoneMissing, function ($id) use ($reachableSet) { return isset($reachableSet[$id]); })));
        $classification->setPhoneOptout(array_values(array_filter($phoneOptout, function ($id) use ($reachableSet) { return isset($reachableSet[$id]); })));
        $classification->setEmailMissing(array_values(array_filter($emailMissing, function ($id) use ($reachableSet) { return isset($reachableSet[$id]); })));
        $classification->setEmailOptout(array_values(array_filter($emailOptout, function ($id) use ($reachableSet) { return isset($reachableSet[$id]); })));

        return $classification;
    }

    public function extractAudience(array $data) : array
    {
        $volunteerIds = array_merge(
            $data['volunteers'] ?? [],
            $data['external_ids'] ? $this->volunteerManager->getIdsByExternalIds($data['external_ids']) : [],
            $data['preselection_key'] ? $this->expirableManager->get($data['preselection_key'])['volunteers'] : []
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

        $badgeIds = array_map(function (Badge $badge) {
            return $badge->getId();
        }, $badgeList);

        if (!empty($badgeIds)) {
            $batchCounts = $this->volunteerManager->getVolunteerCountsPerBadgeInStructures($structureIds, $badgeIds);

            // Ensure every badge ID is present (0 if no structures selected or no volunteers match)
            foreach ($badgeIds as $id) {
                $counts[$id] = $batchCounts[$id] ?? 0;
            }
        }

        return $counts;
    }
}
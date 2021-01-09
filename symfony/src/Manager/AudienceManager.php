<?php

namespace App\Manager;

use App\Entity\Badge;
use App\Entity\Volunteer;
use App\Model\Classification;
use Symfony\Component\Form\FormInterface;
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

    public function classifyAudience(FormInterface $form) : Classification
    {
        // Extracting form data
        $data = [];
        foreach ($form as $name => $element) {
            $data[$name] = $element->getData();
        }

        $classification = new Classification();
        if ($data['nivols']) {
            $classification->invalid = $this->volunteerManager->filterInvalidNivols($data['nivols']);
        }

        $audience = $this->extractAudience($data);

        $classification->disabled = $this->volunteerManager->filterDisabled($audience);

        if (!$this->security->isGranted('ROLE_ADMIN')) {
            $classification->inaccessible = $this->volunteerManager->filterInaccessibles($audience);
        }

        // Reducing the audience to potentially reachable volunteers
        $audience                  = array_diff($audience, $classification->disabled, $classification->inaccessible);
        $classification->reachable = $audience;

        // Adding more contextual information in order to help understanding why volunteers weren't triggered

        /*
    public $phoneLandline = [];
    public $phoneMissing  = [];
    public $phoneOptout   = [];
    public $emailMissing  = [];
    public $emailOptout   = [];
    public $reachable     = [];
*/

        return $classification;
    }

    private function extractAudience(array $data) : array
    {
        if (true === ($data['test_on_me'] ?? false)) {
            return [$this->userManager->findForCurrentUser()->getVolunteer()->getId()];
        }

        $volunteerIds = array_merge(
            $data['volunteers'] ?? [],
            $data['nivols'] ? $this->volunteerManager->getIdsByNivols($data['nivols']) : []
        );

        $structureIds = array_unique(array_merge(
            explode(',', $data['structures_local']),
            $this->structureManager->getDescendantStructures($data['structures_global'])
        ));

        if (true === ($data['badges_all'] ?? false)) {
            $volunteerIds = array_merge(
                $volunteerIds,
                $this->volunteerManager->getVolunteerListInStructures($structureIds)
            );
        } else {
            $badgeIds = array_unique(array_merge($data['badges_ticked'], $data['badges_searched']));

            $volunteerIds = array_merge(
                $volunteerIds,
                $this->volunteerManager->getVolunteerListInStructuresHavingBadges($structureIds, $badgeIds)
            );
        }

        return array_filter(array_unique($volunteerIds));
    }
}
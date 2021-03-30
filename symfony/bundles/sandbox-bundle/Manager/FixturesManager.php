<?php

namespace Bundles\SandboxBundle\Manager;

use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Manager\BadgeManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Tools\Random;

class FixturesManager
{
    /**
     * @var StructureManager
     */
    private $structureManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    /**
     * @var BadgeManager
     */
    private $badgeManager;

    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * @var AnonymizeManager
     */
    private $anonymizeManager;

    public function __construct(StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        BadgeManager $badgeManager,
        UserManager $userManager,
        AnonymizeManager $anonymizeManager)
    {
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
        $this->badgeManager     = $badgeManager;
        $this->userManager      = $userManager;
        $this->anonymizeManager = $anonymizeManager;
    }

    public function createStructure(string $name, ?int $parent, int $numberOfVolunteers, bool $bindToUser) : Structure
    {
        if ($structure = $this->structureManager->findOneByName($name)) {
            return $structure;
        }

        $structure = new Structure();
        $structure->setName($name);
        $structure->setExternalId(Random::generate(8));
        $structure->setEnabled(true);
        if ($parent && $parentStructure = $this->structureManager->find($parent)) {
            $structure->setParentStructure($parentStructure);
        }

        $this->structureManager->save($structure);

        $this->createVolunteers($numberOfVolunteers, $structure->getId());

        if ($bindToUser) {
            $me = $this->userManager->findForCurrentUser();
            $me->addStructure($structure);
            $this->userManager->save($me);
        }

        return $structure;
    }

    public function createVolunteers(int $numberOfVolunteers, ?int $structureId) : array
    {
        $badges = $this->badgeManager->getPublicBadges();

        if ($structureId) {
            $structure = $this->structureManager->find($structureId);
            if (!$structure) {
                return [];
            }
        }

        $volunteers = [];
        for ($i = 0; $i < $numberOfVolunteers; $i++) {
            $volunteer = $this->createVolunteer($badges);
            if ($structureId) {
                $volunteer->addStructure($structure);
                $volunteers[] = $this->volunteerManager->save($volunteer);
            }
        }

        return $volunteers;
    }

    /**
     * @return Volunteer
     */
    private function createVolunteer(array $allBadges) : Volunteer
    {
        $nivol = $this->generateNivol();

        $volunteer = new Volunteer();
        $volunteer->setNivol($nivol);
        $volunteer->setEnabled(true);
        $volunteer->setIdentifier($nivol);
        $volunteer->setLocked(true);
        $volunteer->setMinor(false);

        $badges = [];
        for ($i = 0; $i < 4; $i++) {
            $badges[] = $allBadges[rand() % count($allBadges)];
        }

        foreach (array_unique($badges) as $badge) {
            $volunteer->addBadge($badge);
        }

        $this->volunteerManager->save($volunteer);

        $this->anonymizeManager->anonymizeVolunteer($volunteer->getNivol());

        return $volunteer;
    }

    /**
     * @return string
     */
    private function generateNivol() : string
    {
        $nivol = Random::generate(12, '0123456789ABCDEF');

        if ($this->volunteerManager->findOneByNivol($nivol)) {
            return $this->generateNivol();
        }

        return $nivol;
    }

}
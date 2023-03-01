<?php

namespace Bundles\SandboxBundle\Manager;

use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Manager\BadgeManager;
use App\Manager\StructureManager;
use App\Manager\UserManager;
use App\Manager\VolunteerManager;
use App\Security\Helper\Security;
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

    /**
     * @var Security
     */
    private $security;

    public function __construct(StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        BadgeManager $badgeManager,
        UserManager $userManager,
        AnonymizeManager $anonymizeManager,
        Security $security)
    {
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
        $this->badgeManager     = $badgeManager;
        $this->userManager      = $userManager;
        $this->anonymizeManager = $anonymizeManager;
        $this->security         = $security;
    }

    public function createStructure(string $platform,
        string $name,
        ?int $parent,
        int $numberOfVolunteers,
        bool $bindToUser) : Structure
    {
        if ($structure = $this->structureManager->findOneByName($platform, $name)) {
            return $structure;
        }

        $structure = new Structure();
        $structure->setPlatform($this->security->getPlatform());
        $structure->setName($name);
        $structure->setExternalId(Random::generate(8));
        $structure->setEnabled(true);
        if ($parent && $parentStructure = $this->structureManager->find($parent)) {
            $structure->setParentStructure($parentStructure);
        }

        $this->structureManager->save($structure);

        $this->createVolunteers($numberOfVolunteers, $structure->getId());

        if ($bindToUser) {
            $me = $this->security->getUser();
            $me->addStructure($structure);
            $this->userManager->save($me);
        }

        return $structure;
    }

    public function createVolunteers(int $numberOfVolunteers, ?int $structureId) : array
    {
        $badges = $this->badgeManager->getPublicBadges(
            $this->security->getPlatform()
        );

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
        $platform = $this->security->getPlatform();

        $externalId = $this->generateExternalId($platform);

        $volunteer = new Volunteer();
        $volunteer->setPlatform($platform);
        $volunteer->setExternalId($externalId);
        $volunteer->setEnabled(true);
        $volunteer->setLocked(true);
        $volunteer->setMinor(rand() % 10 === 0);

        $badges = [];
        for ($i = 0; $i < 4; $i++) {
            $badges[] = $allBadges[rand() % count($allBadges)];
        }

        foreach (array_unique($badges) as $badge) {
            $volunteer->addBadge($badge);
        }

        $this->volunteerManager->save($volunteer);

        $this->anonymizeManager->anonymizeVolunteer($volunteer->getExternalId(), $platform);

        return $volunteer;
    }

    private function generateExternalId(string $platform) : string
    {
        $externalId = Random::generate(12, '0123456789ABCDEF');

        if ($this->volunteerManager->findOneByExternalId($platform, $externalId)) {
            return $this->generateExternalId($platform);
        }

        return $externalId;
    }
}
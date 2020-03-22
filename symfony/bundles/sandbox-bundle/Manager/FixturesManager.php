<?php

namespace Bundles\SandboxBundle\Manager;

use App\Entity\Structure;
use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Manager\StructureManager;
use App\Manager\TagManager;
use App\Manager\UserInformationManager;
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
     * @var TagManager
     */
    private $tagManager;

    /**
     * @var UserInformationManager
     */
    private $userInformationManager;

    /**
     * @var AnonymizeManager
     */
    private $anonymizeManager;

    /**
     * @param StructureManager $structureManager
     * @param VolunteerManager $volunteerManager
     * @param TagManager       $tagManager
     * @param AnonymizeManager $anonymizeManager
     */
    public function __construct(StructureManager $structureManager, VolunteerManager $volunteerManager, TagManager $tagManager, UserInformationManager $userInformationManager, AnonymizeManager $anonymizeManager)
    {
        $this->structureManager = $structureManager;
        $this->volunteerManager = $volunteerManager;
        $this->tagManager = $tagManager;
        $this->userInformationManager = $userInformationManager;
        $this->anonymizeManager = $anonymizeManager;
    }

    public function createTags()
    {
        foreach (Tag::TAGS as $label) {
            $tag = new Tag();
            $tag->setLabel($label);
            $this->tagManager->create($tag);
        }
    }

    public function createStructure(string $name, ?int $parent, int $numberOfVolunteers, bool $bindToUser)
    {
        if ($this->structureManager->findOneByName($name)) {
            return;
        }

        $structure = new Structure();
        $structure->setName($name);
        $structure->setIdentifier(Random::generate(8, '0123456789'));
        $structure->setType('UL');
        $structure->setEnabled(true);
        if ($parent && $parentStructure = $this->structureManager->find($parent)) {
            $structure->setParentStructure($parentStructure);
        }

        $this->structureManager->save($structure);

        $this->createVolunteers($numberOfVolunteers, $structure->getId());

        if ($bindToUser) {
            $me = $this->userInformationManager->findForCurrentUser();
            $me->addStructure($structure);
            $this->userInformationManager->save($me);
        }

    }

    /**
     * @param int      $numberOfVolunteers
     * @param int|null $structureId
     */
    public function createVolunteers(int $numberOfVolunteers, ?int $structureId)
    {
        if ($structureId) {
            $structure = $this->structureManager->find($structureId);
            if (!$structure) {
                return;
            }
        }

        for ($i = 0; $i < $numberOfVolunteers; $i++) {
            $volunteer = $this->createVolunteer();
            if ($structureId) {
                $volunteer->addStructure($structure);
                $this->volunteerManager->save($volunteer);
            }
        }
    }

    /**
     * @return Volunteer
     */
    private function createVolunteer(): Volunteer
    {
        $nivol = $this->generateNivol();
        $allTags = $this->tagManager->findAll();

        $volunteer = new Volunteer();
        $volunteer->setNivol($nivol);
        $volunteer->setEnabled(true);
        $volunteer->setIdentifier($nivol);
        $volunteer->setLocked(true);
        $volunteer->setMinor(false);

        $tags = [];
        for ($i = 0; $i < 4; $i++) {
            $tags[] = Tag::TAGS[rand() % count(Tag::TAGS)];
        }

        foreach (array_unique($tags) as $tag) {
            $volunteer->getTags()->add($allTags[$tag]);
        }

        $this->volunteerManager->save($volunteer);

        $this->anonymizeManager->anonymizeVolunteer($volunteer->getNivol());

        return $volunteer;
    }

    /**
     * @return string
     */
    private function generateNivol(): string
    {
        $nivol = Random::generate(12, '0123456789ABCDEF');

        if ($this->volunteerManager->findOneByNivol($nivol)) {
            return $this->generateNivol();
        }

        return $nivol;
    }

}
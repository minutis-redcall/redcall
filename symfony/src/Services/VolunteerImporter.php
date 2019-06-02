<?php

namespace App\Services;

use App\Entity\Volunteer;
use App\Repository\OrganizationRepository;
use App\Repository\TagRepository;
use App\Repository\VolunteerImportRepository;
use App\Repository\VolunteerRepository;

class VolunteerImporter
{
    protected $organizationRepository;
    protected $volunteerRepository;
    protected $tagRepository;
    protected $pegass;

    /**
     * VolunteerImporter constructor.
     *
     * @param OrganizationRepository $organizationRepository
     * @param VolunteerRepository    $volunteerRepository
     * @param TagRepository          $tagRepository
     * @param Pegass                 $pegass
     */
    public function __construct(
        OrganizationRepository $organizationRepository,
        VolunteerRepository $volunteerRepository,
        TagRepository $tagRepository,
        Pegass $pegass)
    {
        $this->organizationRepository = $organizationRepository;
        $this->volunteerRepository    = $volunteerRepository;
        $this->tagRepoistory          = $tagRepository;
        $this->pegass                 = $pegass;
    }

    public function importOrganizationVolunteers(string $organizationCode)
    {
        /* @var \App\Entity\Organization $organization */
        $organization = $this->organizationRepository->findOneByCode($organizationCode);
        if (!$organization) {
            throw new \LogicException(sprintf('Organization code not found: %s', $organizationCode));
        }

        $current = $this->volunteerRepository->findBy([
            'organization' => $organization->getId(),
            'enabled' => true,
        ]);
        $currentNivols = array_map(function(Volunteer $volunteer) {
            return $volunteer->getNivol();
        }, $current);

        $imported = $this->pegass->listVolunteers($organizationCode);
        $importedNivols = array_keys($imported);

        if (count($imported) == 0) {
            throw new \RuntimeException(sprintf('Organization %s contain no volunteers', $organization->getName()));
        }

        // Disable all volunteers that are not anymore in the organization
        $nivolsToDisable = array_diff($currentNivols, $importedNivols);
        $this->volunteerRepository->disableByNivols($nivolsToDisable);
        foreach ($current as $index => $volunteer) {
            if (in_array($volunteer->getNivol(), $nivolsToDisable)) {
                unset($current[$index]);
            }
        }

        // Import or update all other volunteers
        foreach ($imported as $volunteer) {
            $volunteer->setOrganization($organization);
            $this->volunteerRepository->import($volunteer);
        }

        $organization->setLastVolunteerImport(new \DateTime());

        $this->organizationRepository->save($organization);
    }

    public function importVolunteersSkills(int $sleepTime, int $limit)
    {
        $volunteers = $this->volunteerRepository->findVolunteersToRefresh($limit);

        foreach ($volunteers as $volunteer) {
            /* @var \App\Entity\Volunteer $volunteer */
            $skills = $this->pegass->getVolunteerTags($volunteer->getNivol());

            foreach ($skills as $skill) {
                if (!$volunteer->hasTag($skill)) {
                    $volunteer->getTags()->add($this->tagRepoistory->findOneByLabel($skill));
                }
            }

            $volunteer->setLastPegassUpdate(new \DateTime());

            $this->volunteerRepository->save($volunteer);

            sleep($sleepTime);
        }
    }
}

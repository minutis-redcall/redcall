<?php

namespace App\Services;

use App\Entity\Tag;
use App\Entity\Volunteer;
use App\Repository\OrganizationRepository;
use App\Repository\TagRepository;
use App\Repository\VolunteerImportRepository;
use App\Repository\VolunteerRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class VolunteerImporter
{
    protected $organizationRepository;
    protected $volunteerRepository;
    protected $tagRepository;
    protected $pegass;

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
    }

    public function importVolunteersSkills(string $volunteerCode)
    {

    }
}

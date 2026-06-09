<?php

namespace App\Manager;

use App\Entity\DeletedVolunteer;
use App\Entity\Volunteer;
use App\Repository\DeletedVolunteerRepository;
use App\Tools\Hash;
use App\Tools\Random;

class DeletedVolunteerManager
{
    private DeletedVolunteerRepository $deletedVolunteerRepository;

    private VolunteerManager $volunteerManager;

    public function __construct(DeletedVolunteerRepository $deletedVolunteerRepository)
    {
        $this->deletedVolunteerRepository = $deletedVolunteerRepository;
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function setVolunteerManager(VolunteerManager $volunteerManager) : void
    {
        $this->volunteerManager = $volunteerManager;
    }

    public function isDeleted(string $externalId) : bool
    {
        $paddedId = str_pad($externalId, 12, '0', STR_PAD_LEFT);

        $original = $this->deletedVolunteerRepository->findOneByHashedExternalId(
            Hash::hash($externalId)
        );

        $padded = $this->deletedVolunteerRepository->findOneByHashedExternalId(
            Hash::hash($paddedId)
        );

        return $original || $padded;
    }

    public function undelete(string $externalId)
    {
        $deleted = $this->deletedVolunteerRepository->findOneByHashedExternalId(
            Hash::hash($externalId)
        );

        if ($deleted) {
            $this->deletedVolunteerRepository->remove($deleted);
        }
    }

    public function anonymize(Volunteer $volunteer)
    {
        $id = Hash::hash($volunteer->getExternalId());

        if (!$this->deletedVolunteerRepository->findOneByHashedExternalId($id)) {
            $deletedVolunteer = new DeletedVolunteer();
            $deletedVolunteer->setHashedExternalId($id);

            $this->deletedVolunteerRepository->add($deletedVolunteer);
        }

        // Generate a fresh external id, retrying if another volunteer already has it
        do {
            $externalId = sprintf('deleted-%s', Random::generate(16));
        } while ($this->volunteerManager->findOneByExternalId($externalId));

        $volunteer->setExternalId($externalId);
        $this->volunteerManager->save($volunteer);
    }
}

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

    /**
     * GDPR-erase: register the NIVOL in the `deleted_volunteer` registry (so
     * the admin "undelete" UI knows it was deliberately removed) AND release
     * the volunteer's external_id so the same NIVOL never resurrects as the
     * same row. Use this from human-driven paths (admin manual delete,
     * volunteer's own GDPR self-delete from /space). NEVER from sync — the
     * sync path drops volunteers for operational reasons (gone from CSV),
     * not for a legal right-to-be-forgotten request, and historically
     * polluted the GDPR registry with thousands of false entries.
     */
    public function markGdprDeleted(Volunteer $volunteer) : void
    {
        $id = Hash::hash($volunteer->getExternalId());

        if (!$this->deletedVolunteerRepository->findOneByHashedExternalId($id)) {
            $deletedVolunteer = new DeletedVolunteer();
            $deletedVolunteer->setHashedExternalId($id);

            $this->deletedVolunteerRepository->add($deletedVolunteer);
        }

        $this->releaseExternalId($volunteer);
    }

    /**
     * Rename the volunteer's external_id to a fresh `deleted-XXX` so the
     * original NIVOL becomes available for a future re-import (the next sync
     * run will create a new row with the real NIVOL when the volunteer
     * reappears in the CSV). Does NOT touch the GDPR registry — call
     * {@see markGdprDeleted} for that.
     */
    public function releaseExternalId(Volunteer $volunteer) : void
    {
        // Generate a fresh external id, retrying if another volunteer already has it
        do {
            $externalId = sprintf('deleted-%s', Random::generate(16));
        } while ($this->volunteerManager->findOneByExternalId($externalId));

        $volunteer->setExternalId($externalId);
        $this->volunteerManager->save($volunteer);
    }
}

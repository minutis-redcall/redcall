<?php

namespace App\Manager;

use App\Entity\DeletedVolunteer;
use App\Entity\Pegass;
use App\Entity\Volunteer;
use App\Repository\DeletedVolunteerRepository;
use App\Tools\Hash;
use App\Tools\Random;

class DeletedVolunteerManager
{
    /**
     * @var DeletedVolunteerRepository
     */
    private $deletedVolunteerRepository;

    /**
     * @var PegassManager
     */
    private $pegassManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(DeletedVolunteerRepository $deletedVolunteerRepository)
    {
        $this->deletedVolunteerRepository = $deletedVolunteerRepository;
    }

    /**
     * @required
     */
    public function setPegassManager(PegassManager $pegassManager) : void
    {
        $this->pegassManager = $pegassManager;
    }

    /**
     * @required
     */
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
        // Adding an entry on the deleted volunteers table
        $id = Hash::hash($volunteer->getExternalId());

        if (!$this->deletedVolunteerRepository->findOneByHashedExternalId($id)) {
            $deletedVolunteer = new DeletedVolunteer();
            $deletedVolunteer->setHashedExternalId($id);

            $this->deletedVolunteerRepository->add($deletedVolunteer);
        }

        // Generating a new external id
        do {
            $externalId = sprintf('deleted-%s', Random::generate(16));
        } while ($this->pegassManager->getEntity(Pegass::TYPE_VOLUNTEER, $externalId));

        // Removing Pegass entry (useless for accounting purposes)
        $pegass = $this->pegassManager->getEntity(Pegass::TYPE_VOLUNTEER, $volunteer->getExternalId());
        if ($pegass) {
            $this->pegassManager->delete($pegass);
        }

        // Updating volunteer's external id
        $volunteer->setExternalId($externalId);
        $this->volunteerManager->save($volunteer);
    }
}
<?php

namespace App\Entity;

use App\Repository\DeletedVolunteerRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Purpose of this class is to store a hashmac of the volunteer's external id
 * in order to not re-import it during any synchronization.
 *
 * @ORM\Entity(repositoryClass=DeletedVolunteerRepository::class)
 * @ORM\HasLifecycleCallbacks()
 */
class DeletedVolunteer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $hashedExternalId;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private $insertedAt;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getHashedExternalId() : ?string
    {
        return $this->hashedExternalId;
    }

    public function setHashedExternalId(string $hashedExternalId) : self
    {
        $this->hashedExternalId = $hashedExternalId;

        return $this;
    }

    public function getInsertedAt() : ?\DateTimeImmutable
    {
        return $this->insertedAt;
    }

    public function setInsertedAt(\DateTimeImmutable $insertedAt) : self
    {
        $this->insertedAt = $insertedAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setInsertedAt(new \DateTimeImmutable());
    }
}

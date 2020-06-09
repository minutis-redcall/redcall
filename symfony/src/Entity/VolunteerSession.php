<?php

namespace App\Entity;

use App\Repository\VolunteerSessionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VolunteerSessionRepository::class)
 * @ORM\Table(
 * uniqueConstraints={
 *     @ORM\UniqueConstraint(name="session_id_idx", columns={"session_id"})
 * })
 */
class VolunteerSession
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Volunteer::class, fetch="EAGER")
     * @ORM\JoinColumn(nullable=false)
     */
    private $volunteer;

    /**
     * @ORM\Column(type="string", length=36)
     */
    private $sessionId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVolunteer(): ?Volunteer
    {
        return $this->volunteer;
    }

    public function setVolunteer(?Volunteer $volunteer): self
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}

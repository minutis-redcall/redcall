<?php

namespace App\Entity;

use App\Repository\VolunteerAuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Append-only record of an anonymize event on a Volunteer.
 *
 * Deliberately PII-free: only the NIVOL (a public reference), the bound
 * user's UUID (for cross-reference with user_audit_log), the actor (admin
 * user FK + a denormalised label), and a structural snapshot are stored.
 * No name, no email, no phone. Reading the log to understand "who killed
 * which volunteer and why" must not re-introduce the PII that the
 * anonymize itself just wiped.
 */
#[ORM\Entity(repositoryClass: VolunteerAuditLogRepository::class)]
#[ORM\Table(name: 'volunteer_audit_log')]
#[ORM\Index(name: 'volunteer_audit_log_created_at_idx', columns: ['created_at'])]
#[ORM\Index(name: 'volunteer_audit_log_target_external_id_idx', columns: ['target_external_id'])]
#[ORM\Index(name: 'volunteer_audit_log_target_bound_user_id_idx', columns: ['target_bound_user_id'])]
#[ORM\HasLifecycleCallbacks]
class VolunteerAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'string', length: 16)]
    private $action;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private $actor;

    #[ORM\Column(type: 'string', length: 64)]
    private $actorLabel;

    #[ORM\ManyToOne(targetEntity: Volunteer::class)]
    #[ORM\JoinColumn(name: 'target_volunteer_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private $targetVolunteer;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private $targetExternalId;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private $targetBoundUserId;

    #[ORM\Column(type: 'text')]
    private $snapshot;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getCreatedAt() : ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt) : self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAction() : ?string
    {
        return $this->action;
    }

    public function setAction(string $action) : self
    {
        $this->action = $action;

        return $this;
    }

    public function getActor() : ?User
    {
        return $this->actor;
    }

    public function setActor(?User $actor) : self
    {
        $this->actor = $actor;

        return $this;
    }

    public function getActorLabel() : ?string
    {
        return $this->actorLabel;
    }

    public function setActorLabel(string $actorLabel) : self
    {
        $this->actorLabel = $actorLabel;

        return $this;
    }

    public function getTargetVolunteer() : ?Volunteer
    {
        return $this->targetVolunteer;
    }

    public function setTargetVolunteer(?Volunteer $targetVolunteer) : self
    {
        $this->targetVolunteer = $targetVolunteer;

        return $this;
    }

    public function getTargetExternalId() : ?string
    {
        return $this->targetExternalId;
    }

    public function setTargetExternalId(?string $targetExternalId) : self
    {
        $this->targetExternalId = $targetExternalId;

        return $this;
    }

    public function getTargetBoundUserId() : ?string
    {
        return $this->targetBoundUserId;
    }

    public function setTargetBoundUserId(?string $targetBoundUserId) : self
    {
        $this->targetBoundUserId = $targetBoundUserId;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSnapshot() : array
    {
        return json_decode((string) $this->snapshot, true) ?: [];
    }

    /**
     * @param array<string, mixed> $snapshot
     */
    public function setSnapshot(array $snapshot) : self
    {
        $this->snapshot = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $this;
    }

    #[ORM\PrePersist]
    public function onPrePersist() : void
    {
        if (null === $this->createdAt) {
            $this->createdAt = new \DateTime();
        }
    }
}

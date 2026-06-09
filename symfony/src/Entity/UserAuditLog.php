<?php

namespace App\Entity;

use App\Repository\UserAuditLogRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Append-only record of a sensitive change to App\Entity\User (create,
 * privilege/structure update, delete).
 *
 * Rows are written through App\Manager\UserAuditLogManager from every
 * mutation site (admin controller, CLI commands, sync). Both the actor
 * and the target are referenced by FK *and* denormalised — the FK can go
 * NULL when the underlying user is hard-deleted, the denormalised fields
 * keep the row searchable forever.
 */
#[ORM\Entity(repositoryClass: UserAuditLogRepository::class)]
#[ORM\Table(name: 'user_audit_log')]
#[ORM\Index(name: 'user_audit_log_created_at_idx', columns: ['created_at'])]
#[ORM\Index(name: 'user_audit_log_target_username_idx', columns: ['target_username'])]
#[ORM\Index(name: 'user_audit_log_target_external_id_idx', columns: ['target_external_id'])]
#[ORM\Index(name: 'user_audit_log_target_display_name_idx', columns: ['target_display_name'])]
#[ORM\HasLifecycleCallbacks]
class UserAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'string', length: 10)]
    private $action;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'actor_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private $actor;

    #[ORM\Column(type: 'string', length: 64)]
    private $actorLabel;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'target_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private $targetUser;

    #[ORM\Column(type: 'string', length: 80, nullable: true)]
    private $targetUsername;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private $targetExternalId;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $targetDisplayName;

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

    public function getTargetUser() : ?User
    {
        return $this->targetUser;
    }

    public function setTargetUser(?User $targetUser) : self
    {
        $this->targetUser = $targetUser;

        return $this;
    }

    public function getTargetUsername() : ?string
    {
        return $this->targetUsername;
    }

    public function setTargetUsername(?string $targetUsername) : self
    {
        $this->targetUsername = $targetUsername;

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

    public function getTargetDisplayName() : ?string
    {
        return $this->targetDisplayName;
    }

    public function setTargetDisplayName(?string $targetDisplayName) : self
    {
        $this->targetDisplayName = $targetDisplayName;

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

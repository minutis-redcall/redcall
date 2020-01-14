<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PegassRepository")
 * @ORM\Table(
 * uniqueConstraints={
 *     @ORM\UniqueConstraint(name="type_identifier_idx", columns={"type", "identifier"})
 * },
 * indexes={
 *    @ORM\Index(name="type_update_idx", columns={"type", "updated_at"}),
 *    @ORM\Index(name="type_identifier_parent_idx", columns={"type", "identifier", "parent_identifier"})
 * })
 * @ORM\HasLifecycleCallbacks
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Pegass
{
    const TYPE_AREA       = 'area';
    const TYPE_DEPARTMENT = 'department';
    const TYPE_STRUCTURE  = 'structure';
    const TYPE_VOLUNTEER  = 'volunteer';

    const TTL = [
        self::TYPE_AREA       => 365 * 24 * 60 * 60, // 1 year
        self::TYPE_DEPARTMENT => 7 * 24 * 60 * 60, // 1 week
        self::TYPE_STRUCTURE  => 7 * 24 * 60 * 60, // 1 week
        self::TYPE_VOLUNTEER  => 30 * 24 * 60 * 60, // 1 month
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $identifier;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $parentIdentifier;

    /**
     * @ORM\Column(type="string", length=24)
     */
    private $type;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    private $lockUpdateDate = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    public function getParentIdentifier(): ?string
    {
        return $this->parentIdentifier;
    }

    public function setParentIdentifier(string $parentIdentifier): self
    {
        $this->parentIdentifier = $parentIdentifier;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function lockUpdateDate(): self
    {
        $this->lockUpdateDate = true;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        if (!$this->updatedAt) {
            $this->setUpdatedAt(new \DateTime());
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        if (!$this->lockUpdateDate) {
            $this->setUpdatedAt(new \DateTime());
        }
    }
}

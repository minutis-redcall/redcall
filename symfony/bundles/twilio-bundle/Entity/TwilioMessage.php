<?php

namespace Bundles\TwilioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uuid_idx", columns={"uuid"})
 *     },
 *     indexes={
 *         @ORM\Index(name="sid_idx", columns={"sid"}),
 *         @ORM\Index(name="price_idx", columns={"price"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="TwilioMessageRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TwilioMessage
{
    const DIRECTION_INBOUND  = 'inbound'; // messages received
    const DIRECTION_OUTBOUND = 'outbound'; // messages sent

    const STATUS_QUEUED      = 'queued';
    const STATUS_ACCEPTED    = 'accepted';
    const STATUS_SENT        = 'sent';
    const STATUS_DELIVERED   = 'delivered';
    const STATUS_UNDELIVERED = 'undelivered';
    const STATUS_FAILED      = 'failed';
    const STATUS_RECEIVING   = 'receiving';
    const STATUS_RECEIVED    = 'received';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=36)
     */
    private $uuid;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $direction;

    /**
     * @ORM\Column(type="string", length=4096)
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $fromNumber;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $toNumber;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $sid;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $status;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $price;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $unit;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $context;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getDirection(): ?string
    {
        return $this->direction;
    }

    public function setDirection(string $direction): self
    {
        $this->direction = $direction;

        return $this;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getFromNumber(): ?string
    {
        return $this->fromNumber;
    }

    public function setFromNumber(string $fromNumber): self
    {
        $this->fromNumber = $fromNumber;

        return $this;
    }

    public function getToNumber(): ?string
    {
        return $this->toNumber;
    }

    public function setToNumber(string $toNumber): self
    {
        $this->toNumber = $toNumber;

        return $this;
    }

    public function getSid(): ?string
    {
        return $this->sid;
    }

    public function setSid(string $sid): self
    {
        $this->sid = $sid;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getContext()
    {
        return $this->context ? json_decode($this->context, true) : null;
    }

    public function setContext($context): self
    {
        $this->context = json_encode($context);

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setCreatedAt(new \DateTime());
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->setUpdatedAt(new \DateTime());
    }
}

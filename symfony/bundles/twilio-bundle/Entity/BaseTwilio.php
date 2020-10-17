<?php

namespace Bundles\TwilioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\MappedSuperclass()
 */
abstract class BaseTwilio
{
    const DIRECTION_INBOUND  = 'inbound'; // message/call received
    const DIRECTION_OUTBOUND = 'outbound'; // message/call sent

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=36)
     */
    protected $uuid;

    /**
     * @ORM\Column(type="string", length=16)
     */
    protected $direction;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $message;

    /**
     * @ORM\Column(type="string", length=16)
     */
    protected $fromNumber;

    /**
     * @ORM\Column(type="string", length=16)
     */
    protected $toNumber;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $sid;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    protected $status;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $price;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $unit;

    /**
     * Number of tries fetching the message price on Twilio side.
     *
     * Sometimes price can stay null in case of invalid statuses,
     * so this retry system prevents infinitely scrapping Twilio
     * API for the price of an SMS that will indefinitely stay
     * null.
     *
     * @ORM\Column(type="integer")
     */
    protected $retry = 0;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $context;
    /**
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;
    /**
     * @ORM\Column(type="datetime")
     */
    protected $updatedAt;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $error;

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

    /**
     * @return int
     */
    public function getRetry(): int
    {
        return $this->retry;
    }

    /**
     * @param int $retry
     *
     * @return TwilioMessage
     */
    public function setRetry(int $retry): self
    {
        $this->retry = $retry;

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

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): self
    {
        $this->error = mb_substr($error, 0, 255);

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

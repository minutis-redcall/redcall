<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CostRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Cost
{
    const DIRECTION_INBOUND  = 'received';
    const DIRECTION_OUTBOUND = 'sent';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Message", inversedBy="costs")
     */
    private $message;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $direction;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $fromNumber;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $toNumber;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $price;

    /**
     * @ORM\Column(type="string", length=3)
     */
    private $currency;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

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

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        if (null === $this->createdAt) {
            $this->setCreatedAt(new \DateTime());
        }
    }
}

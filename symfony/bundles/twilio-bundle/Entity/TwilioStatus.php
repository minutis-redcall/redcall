<?php

namespace Bundles\TwilioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Bundles\TwilioBundle\Repository\TwilioStatusRepository")
 */
class TwilioStatus
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $sid;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $receivedAt;

    public function __construct()
    {
        $this->receivedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getReceivedAt(): ?string
    {
        return $this->receivedAt;
    }

    public function setReceivedAt(string $receivedAt): self
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PegassRepository")
 */
class Pegass
{
    const TYPE_MAIN         = 'main';
    const TYPE_DEPARTMENT   = 'department';
    const TYPE_ORGANIZATION = 'organization';
    const TYPE_VOLUNTEER    = 'volunteer';

    const TTL = [
        self::TYPE_MAIN         => 365 * 24 * 60 * 60, // 1 year
        self::TYPE_DEPARTMENT   => 7 * 24 * 60 * 60, // 1 week
        self::TYPE_ORGANIZATION => 7 * 24 * 60 * 60, // 1 week
        self::TYPE_VOLUNTEER    => 30 * 24 * 60 * 60, // 1 month
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $identifier;

    /**
     * @ORM\Column(type="string", length=24)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

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
}

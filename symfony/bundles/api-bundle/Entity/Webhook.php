<?php

namespace Bundles\ApiBundle\Entity;

use Bundles\ApiBundle\Repository\WebhookRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=WebhookRepository::class)
 */
class Webhook
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $uri;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $fallbackUri;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $secret;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $features;

    /**
     * @ORM\Column(type="integer")
     */
    private $usageCount;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastUsedAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getUri() : ?string
    {
        return $this->uri;
    }

    public function setUri(string $uri) : self
    {
        $this->uri = $uri;

        return $this;
    }

    public function getFallbackUri() : ?string
    {
        return $this->fallbackUri;
    }

    public function setFallbackUri(?string $fallbackUri) : self
    {
        $this->fallbackUri = $fallbackUri;

        return $this;
    }

    public function getSecret() : ?string
    {
        return $this->secret;
    }

    public function setSecret(string $secret) : self
    {
        $this->secret = $secret;

        return $this;
    }

    public function getFeatures() : ?string
    {
        return $this->features;
    }

    public function setFeatures(?string $features) : self
    {
        $this->features = $features;

        return $this;
    }

    public function getUsageCount() : ?int
    {
        return $this->usageCount;
    }

    public function setUsageCount(int $usageCount) : void
    {
        $this->usageCount = $usageCount;
    }

    public function getLastUsedAt() : ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(\DateTimeInterface $lastUsedAt) : void
    {
        $this->lastUsedAt = $lastUsedAt;
    }

    public function getCreatedAt() : ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt) : void
    {
        $this->createdAt = $createdAt;
    }

    public function isOwnedBy(UserInterface $user) : bool
    {
        return $this->username === $user->getUsername();
    }
}

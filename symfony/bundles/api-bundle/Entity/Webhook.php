<?php

namespace Bundles\ApiBundle\Entity;

use Bundles\ApiBundle\Repository\WebhookRepository;
use Doctrine\ORM\Mapping as ORM;

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
}

<?php

namespace Bundles\ApiBundle\Entity;

use Bundles\ApiBundle\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass=TokenRepository::class)
 * @ORM\Table(indexes={
 *     @ORM\Index(name="usernamex", columns={"username"}),
 *     @ORM\Index(name="tokenx", columns={"token"})
 * })
 */
class Token
{
    const NAME_MAX_LENGTH         = 255;
    const CLEARTEXT_SECRET_LENGTH = 64;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=self::NAME_MAX_LENGTH)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=36)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $token;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $secret;

    /**
     * @ORM\Column(type="integer")
     */
    private $usageCount = 0;

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

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    public function getUsername() : ?string
    {
        return $this->username;
    }

    public function setUsername(string $username) : self
    {
        $this->username = $username;

        return $this;
    }

    public function getToken() : ?string
    {
        return $this->token;
    }

    public function setToken(string $token) : self
    {
        $this->token = $token;

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

    public function setCreatedAt(\DateTimeInterface $createdAt) : self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isOwnedBy(UserInterface $user) : bool
    {
        return $this->username === $user->getUsername();
    }

    public function sign(string $method, string $uri, string $body = '') : string
    {
        return hash_hmac('sha256', sprintf('%s%s%s', $method, $uri, $body), $this->secret);
    }

    public function incrementHitCount() : int
    {
        $this->usageCount++;
        $this->lastUsedAt = new \DateTime();

        return $this->usageCount;
    }

    public function __toString() : string
    {
        return $this->token;
    }
}

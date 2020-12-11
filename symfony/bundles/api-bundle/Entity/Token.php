<?php

namespace Bundles\ApiBundle\Entity;

use Bundles\ApiBundle\Repository\TokenRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TokenRepository::class)
 * @ORM\Table(indexes={
 *     @ORM\Index(name="usernamex", columns={"username"}),
 *     @ORM\Index(name="tokenx", columns={"token"})
 * })
 */
class Token
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

    public function getCreatedAt() : ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt) : self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}

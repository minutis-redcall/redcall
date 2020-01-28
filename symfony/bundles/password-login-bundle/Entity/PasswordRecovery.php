<?php

namespace Bundles\PasswordLoginBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="uuid_idx", columns={"uuid"})})
 * @ORM\Entity(repositoryClass="Bundles\PasswordLoginBundle\Repository\PasswordRecoveryRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class PasswordRecovery
{
    const EXPIRATION = '3 hours';

    /**
     * @ORM\Column(name="username", type="string", length=64)
     * @ORM\Id
     */
    private $username;

    /**
     * @ORM\Column(name="uuid", type="string", length=36)
     */
    private $uuid;

    /**
     * @ORM\Column(name="timestamp", type="integer", options={"unsigned"=true})
     */
    private $timestamp;

    public function __construct()
    {
        $this->timestamp = time();
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): PasswordRecovery
    {
        $this->username = $username;

        return $this;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): PasswordRecovery
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): PasswordRecovery
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function hasExpired(): bool
    {
        return $this->timestamp + strtotime(self::EXPIRATION) - time() < time();
    }
}

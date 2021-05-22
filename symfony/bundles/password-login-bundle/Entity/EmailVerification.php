<?php

namespace Bundles\PasswordLoginBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(uniqueConstraints={@ORM\UniqueConstraint(name="uuid_idx", columns={"uuid"})})
 * @ORM\Entity(repositoryClass="Bundles\PasswordLoginBundle\Repository\EmailVerificationRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class EmailVerification
{
    const EXPIRATION = '36 hours';

    const TYPE_REGISTRATION = 'registration';
    const TYPE_EDIT_PROFILE = 'edit_profile';

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
     * @ORM\Column(name="type", type="string", length=36)
     */
    private $type;

    /**
     * @ORM\Column(name="timestamp", type="integer", options={"unsigned"=true})
     */
    private $timestamp;

    public function __construct()
    {
        $this->timestamp = time();
    }

    public function getUsername() : string
    {
        return $this->username;
    }

    public function setUsername(string $username) : EmailVerification
    {
        $this->username = $username;

        return $this;
    }

    public function getUuid() : string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid) : EmailVerification
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getTimestamp() : int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp) : EmailVerification
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    public function hasExpired() : bool
    {
        return $this->timestamp + strtotime(self::EXPIRATION) - time() < time();
    }
}

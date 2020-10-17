<?php

namespace Bundles\PasswordLoginBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\MappedSuperclass()
 */
class AbstractUser implements UserInterface, EquatableInterface
{
    /**
     * @ORM\Column(name="id", type="string", length=36)
     * @ORM\Id
     */
    protected $id;

    /**
     * @ORM\Column(name="username", type="string", length=64, unique=true)
     */
    protected $username;

    /**
     * @ORM\Column(name="password", type="string", length=72)
     */
    protected $password;

    /**
     * @ORM\Column(name="is_verified", type="boolean")
     */
    protected $isVerified = false;

    /**
     * @ORM\Column(name="is_trusted", type="boolean")
     */
    protected $isTrusted = false;

    /**
     * @ORM\Column(name="is_admin", type="boolean")
     */
    protected $isAdmin = false;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $registeredAt;

    protected $roles = ['ROLE_USER'];

    public function __construct()
    {
        $this->id = Uuid::uuid4();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     *
     * @return $this
     */
    public function setUsername(string $username): AbstractUser
    {
        $this->username = $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): AbstractUser
    {
        $this->password = $password;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): AbstractUser
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function isTrusted(): bool
    {
        return $this->isTrusted;
    }

    public function setIsTrusted(bool $isTrusted): AbstractUser
    {
        $this->isTrusted = $isTrusted;

        return $this;
    }

    public function isAdmin(): bool
    {
        return $this->isAdmin;
    }

    public function setIsAdmin(bool $isAdmin): AbstractUser
    {
        $this->isAdmin = $isAdmin;

        return $this;
    }

    public function getRegisteredAt()
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt($registeredAt): AbstractUser
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setRegisteredAt(new DateTime());
    }

    public function eraseCredentials(): void
    {
    }

    public function hasRole($role): bool
    {
        return in_array($role, $this->roles);
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        if ($this->isTrusted) {
            $roles[] = 'ROLE_TRUSTED';
        }

        if ($this->isAdmin) {
            $roles[] = 'ROLE_ADMIN';
        }

        return $roles;
    }

    public function getSalt()
    {
        return null;
    }

    public function isEqualTo(UserInterface $user): bool
    {
        return $user instanceof AbstractUser
               && $user->getUsername() === $this->getUsername()
               && $user->getPassword() === $this->getPassword()
               && $user->isVerified() === $this->isVerified()
               && $user->isTrusted() === $this->isTrusted()
               && $user->isAdmin() === $this->isAdmin();
    }
}

<?php

namespace App\Entity;

use Bundles\PasswordLoginBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserPreferenceRepository")
 */
class UserPreference
{
    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Bundles\PasswordLoginBundle\Entity\User", cascade={"all"})
     * @ORM\JoinColumn(referencedColumnName="username", nullable=false, onDelete="cascade")
     */
    private $user;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $locale;

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     *
     * @return UserPreference
     */
    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * @param string|null $locale
     *
     * @return UserPreference
     */
    public function setLocale(?string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }
}

<?php

namespace App\Entity;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *    indexes={
 *        @ORM\Index(name="platform_idx", columns={"platform"}),
 *        @ORM\Index(name="nivol_idx", columns={"nivol"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 *
 * Parent class has callbacks
 * @ORM\HasLifecycleCallbacks()
 */
class User extends AbstractUser
{
    /**
     * @ORM\Column(type="string", length=5)
     */
    private $platform;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $locale;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $timezone;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    private $nivol;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $isDeveloper = false;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $isRoot = false;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Volunteer", inversedBy="user")
     */
    private $volunteer;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Structure", inversedBy="users")
     * @ORM\OrderBy({"enabled" = "DESC", "identifier" = "ASC"})
     */
    private $structures;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $locked = false;

    public function __construct()
    {
        parent::__construct();

        $this->structures = new ArrayCollection();
    }

    public function getPlatform()
    {
        return $this->platform;
    }

    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    public function getLocale() : ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale) : self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getTimezone() : string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone)
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function getNivol() : ?string
    {
        return $this->nivol;
    }

    public function setNivol(?string $nivol) : self
    {
        $this->nivol = $nivol;

        return $this;
    }

    public function isDeveloper() : bool
    {
        return $this->isDeveloper;
    }

    public function setIsDeveloper(bool $isDeveloper) : self
    {
        $this->isDeveloper = $isDeveloper;

        return $this;
    }

    public function isRoot() : bool
    {
        return $this->isRoot;
    }

    public function setIsRoot(bool $isRoot) : User
    {
        $this->isRoot = $isRoot;

        return $this;
    }

    public function getVolunteer() : ?Volunteer
    {
        return $this->volunteer;
    }

    public function setVolunteer(?Volunteer $volunteer) : self
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    public function getStructures(bool $onlyEnabled = true) : Collection
    {
        if ($onlyEnabled) {
            return $this->getEnabledStructures();
        }

        return $this->structures;
    }

    public function getEnabledStructures() : Collection
    {
        return $this->structures->filter(function (Structure $structure) {
            return $structure->isEnabled();
        });
    }

    public function addStructure(Structure $structure) : self
    {
        if (!$this->structures->contains($structure)) {
            $this->structures[] = $structure;
        }

        return $this;
    }

    public function removeStructure(Structure $structure) : self
    {
        if ($this->structures->contains($structure)) {
            $this->structures->removeElement($structure);
        }

        return $this;
    }

    public function updateStructures(array $structures)
    {
        foreach ($structures as $structure) {
            $this->addStructure($structure);
        }
    }

    public function hasStructure(Structure $structure) : bool
    {
        return $this->structures->contains($structure);
    }

    public function hasCommonStructure($structures) : bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        foreach ($structures as $structure) {
            if ($this->hasStructure($structure)) {
                return true;
            }
        }

        return false;
    }

    public function getCommonStructures($structures) : array
    {
        if ($this->isAdmin()) {
            return $structures;
        }

        $common = [];

        foreach ($structures as $structure) {
            if ($this->hasStructure($structure)) {
                $common[] = $structure;
            }
        }

        return $common;
    }

    /**
     * @return Structure[]
     */
    public function getRootStructures() : array
    {
        $structures = $this->getStructures();
        $roots      = [];
        foreach ($structures as $structure) {
            /** @var Structure $structure */
            if (!$structure->getParentStructure()) {
                $roots[] = $structure;
                continue;
            }

            // Structure disappear if any of its ancestor is in the list
            foreach ($structure->getAncestors() as $ancestor) {
                if (!$structures->contains($ancestor)) {
                    $roots[] = $structure;
                }
            }
        }

        return $roots;
    }

    public function getMainStructure() : ?Structure
    {
        $root = $this->getRootStructures();

        if ($root) {
            return reset($root);
        }

        return null;
    }

    public function isLocked() : ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked) : self
    {
        $this->locked = $locked;

        return $this;
    }


    public function getRoles() : array
    {
        $roles = parent::getRoles();

        if ($this->isDeveloper) {
            $roles[] = 'ROLE_DEVELOPER';
        }

        if ($this->isRoot) {
            $roles[] = 'ROLE_ROOT';
        }

        return $roles;
    }

    public function __clone()
    {
        $this->structures = clone $this->structures;
    }
}
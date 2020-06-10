<?php

namespace App\Entity;

use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 * indexes={
 *    @ORM\Index(name="nivol_idx", columns={"nivol"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 * @ORM\HasLifecycleCallbacks
 */
class User extends AbstractUser
{
    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    private $locale;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    private $nivol;

    /**
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $isDeveloper = false;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Volunteer", inversedBy="user")
     */
    private $volunteer;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Structure", inversedBy="users")
     * @ORM\OrderBy({"identifier" = "ASC"})
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

    public function getLocale() : ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale) : self
    {
        $this->locale = $locale;

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

    public function getVolunteer() : ?Volunteer
    {
        return $this->volunteer;
    }

    public function setVolunteer(?Volunteer $volunteer) : self
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    public function getStructures() : Collection
    {
        return $this->structures->filter(function(Structure $structure) {
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

    public function computeStructureList()
    {
        return $this->structures;
    }

    public function isLocked() : ?bool
    {
        if (0 === getenv('IS_REDCROSS')) {
            return false;
        }

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

        return $roles;
    }
}
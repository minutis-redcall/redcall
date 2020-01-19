<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StructureRepository")
 * @ORM\Table(
 * uniqueConstraints={
 *     @ORM\UniqueConstraint(name="identifier_idx", columns={"identifier"})
 * })
 */
class Structure
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $identifier;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $president;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Volunteer", mappedBy="structures")
     */
    private $volunteers;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Structure", inversedBy="childrenStructures")
     */
    private $parentStructure;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Structure", mappedBy="parentStructure")
     */
    private $childrenStructures;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastPegassUpdate;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\UserInformation", mappedBy="structures")
     */
    private $users;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Campaign", mappedBy="structure")
     */
    private $campaigns;

    public function __construct()
    {
        $this->volunteers         = new ArrayCollection();
        $this->childrenStructures = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->campaigns = new ArrayCollection();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getIdentifier(): ?int
    {
        return $this->identifier;
    }

    /**
     * @param int $identifier
     *
     * @return Structure
     */
    public function setIdentifier(int $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return Structure
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Structure
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPresident(): ?string
    {
        return $this->president;
    }

    /**
     * @param string|null $president
     *
     * @return Structure
     */
    public function setPresident(?string $president): self
    {
        $this->president = $president;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return Structure
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return Collection|Volunteer[]
     */
    public function getVolunteers(): Collection
    {
        return $this->volunteers;
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return Structure
     */
    public function addVolunteer(Volunteer $volunteer): self
    {
        if (!$this->volunteers->contains($volunteer)) {
            $this->volunteers[] = $volunteer;
            $volunteer->addStructure($this);
        }

        return $this;
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return Structure
     */
    public function removeVolunteer(Volunteer $volunteer): self
    {
        if ($this->volunteers->contains($volunteer)) {
            $this->volunteers->removeElement($volunteer);
            $volunteer->removeStructure($this);
        }

        return $this;
    }

    /**
     * @return Structure|null
     */
    public function getParentStructure(): ?self
    {
        return $this->parentStructure;
    }

    /**
     * @param self|null $parentStructure
     *
     * @return Structure
     */
    public function setParentStructure(?self $parentStructure): self
    {
        $this->parentStructure = $parentStructure;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildrenStructures(): Collection
    {
        return $this->childrenStructures;
    }

    /**
     * @param self $childrenStructure
     *
     * @return Structure
     */
    public function addChildrenStructure(self $childrenStructure): self
    {
        if (!$this->childrenStructures->contains($childrenStructure)) {
            $this->childrenStructures[] = $childrenStructure;
            $childrenStructure->setParentStructure($this);
        }

        return $this;
    }

    /**
     * @param self $childrenStructure
     *
     * @return Structure
     */
    public function removeChildrenStructure(self $childrenStructure): self
    {
        if ($this->childrenStructures->contains($childrenStructure)) {
            $this->childrenStructures->removeElement($childrenStructure);
            // set the owning side to null (unless already changed)
            if ($childrenStructure->getParentStructure() === $this) {
                $childrenStructure->setParentStructure(null);
            }
        }

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getLastPegassUpdate(): ?\DateTimeInterface
    {
        return $this->lastPegassUpdate;
    }

    /**
     * @param \DateTimeInterface|null $lastPegassUpdate
     *
     * @return Structure
     */
    public function setLastPegassUpdate(?\DateTimeInterface $lastPegassUpdate): self
    {
        $this->lastPegassUpdate = $lastPegassUpdate;

        return $this;
    }

    /**
     * @return Collection|UserInformation[]
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(UserInformation $user): self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addStructure($this);
        }

        return $this;
    }

    public function removeUser(UserInformation $user): self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeStructure($this);
        }

        return $this;
    }

    /**
     * @return Collection|Campaign[]
     */
    public function getCampaigns(): Collection
    {
        return $this->campaigns;
    }

    public function addCampaign(Campaign $campaign): self
    {
        if (!$this->campaigns->contains($campaign)) {
            $this->campaigns[] = $campaign;
            $campaign->setStructure($this);
        }

        return $this;
    }

    public function removeCampaign(Campaign $campaign): self
    {
        if ($this->campaigns->contains($campaign)) {
            $this->campaigns->removeElement($campaign);
            // set the owning side to null (unless already changed)
            if ($campaign->getStructure() === $this) {
                $campaign->setStructure(null);
            }
        }

        return $this;
    }
}

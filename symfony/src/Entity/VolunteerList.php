<?php

namespace App\Entity;

use App\Repository\VolunteerListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VolunteerListRepository::class)
 */
class VolunteerList
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity=Structure::class, inversedBy="volunteerLists")
     * @ORM\JoinColumn(nullable=false)
     */
    private $structure;

    /**
     * @ORM\ManyToMany(targetEntity=Volunteer::class)
     */
    private $volunteers;

    /**
     * @ORM\Column(type="text")
     */
    private $audience;

    public function __construct()
    {
        $this->volunteers = new ArrayCollection();
    }

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

    public function getStructure() : ?Structure
    {
        return $this->structure;
    }

    public function setStructure(?Structure $structure) : self
    {
        $this->structure = $structure;

        return $this;
    }

    /**
     * @return Collection|Volunteer[]
     */
    public function getVolunteers() : Collection
    {
        return $this->volunteers;
    }

    public function addVolunteer(Volunteer $volunteer) : self
    {
        if (!$this->volunteers->contains($volunteer)) {
            $this->volunteers[] = $volunteer;
        }

        return $this;
    }

    public function removeVolunteer(Volunteer $volunteer) : self
    {
        $this->volunteers->removeElement($volunteer);

        return $this;
    }

    public function getAudience() : ?array
    {
        return $this->audience ? json_decode($this->audience, true) : null;
    }

    public function setAudience(?array $audience) : self
    {
        $this->audience = json_encode($audience);

        return $this;
    }
}

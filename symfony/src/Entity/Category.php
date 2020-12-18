<?php

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull
     * @Assert\Length(min=1, max=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Regex(pattern="/\d+/")
     */
    private $priority;

    /**
     * @ORM\OneToMany(targetEntity=Badge::class, mappedBy="category", cascade={"persist"})
     */
    private $badges;

    public function __construct()
    {
        $this->badges = new ArrayCollection();
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

    public function getPriority() : ?int
    {
        return $this->priority;
    }

    public function setPriority(?int $priority) : self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return Collection|Badge[]
     */
    public function getBadges() : Collection
    {
        return $this->badges;
    }

    public function addBadge(Badge $badge) : self
    {
        if (!$this->badges->contains($badge)) {
            $this->badges[] = $badge;
            $badge->setCategory($this);
        }

        return $this;
    }

    public function removeBadge(Badge $badge) : self
    {
        if ($this->badges->contains($badge)) {
            $this->badges->removeElement($badge);
            // set the owning side to null (unless already changed)
            if ($badge->getCategory() === $this) {
                $badge->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toSearchResults() : array
    {
        return [
            'id'   => (string) $this->getId(),
            'name' => $this->getName(),
        ];
    }
}

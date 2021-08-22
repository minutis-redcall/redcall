<?php

namespace App\Entity;

use App\Contract\LockableInterface;
use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="pf_extid_idx", columns={"platform", "external_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Category implements LockableInterface
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=5)
     */
    private $platform;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64)
     * @Assert\NotNull
     * @Assert\Length(min=1, max=64)
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotNull
     * @Assert\Length(min=1, max=255)
     */
    private $name;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     * @Assert\Regex(pattern="/\d+/")
     */
    private $priority;

    /**
     * @ORM\OneToMany(targetEntity=Badge::class, mappedBy="category", cascade={"persist"})
     */
    private $badges;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 1})
     */
    private $enabled = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $locked = false;

    public function __construct()
    {
        $this->badges = new ArrayCollection();
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getPlatform() : string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform)
    {
        $this->platform = $platform;

        return $this;
    }

    public function getExternalId() : ?string
    {
        return $this->externalId;
    }

    public function setExternalId(?string $externalId)
    {
        $this->externalId = $externalId;

        return $this;
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
    public function getBadges(bool $onlyEnabled = true) : Collection
    {
        if ($onlyEnabled) {
            return $this->getEnabledBadges();
        }

        return $this->badges->filter(function (Badge $badge) {
            return $this->platform === $badge->getPlatform();
        });
    }

    public function getEnabledBadges()
    {
        return $this->badges->filter(function (Badge $badge) {
            return $this->platform === $badge->getPlatform() && $badge->isEnabled();
        });
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

    public function isEnabled() : bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled) : Category
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isLocked() : bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked) : Category
    {
        $this->locked = $locked;

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

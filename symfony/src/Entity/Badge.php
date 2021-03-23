<?php

namespace App\Entity;

use App\Repository\BadgeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="pf_extid_idx", columns={"platform", "external_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass=BadgeRepository::class)
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Badge
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
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $externalId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64)
     * @Assert\NotBlank
     * @Assert\Length(max="64")
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    private $description;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @Assert\Range(min="0", max="1000")
     */
    private $renderingPriority = 0;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @Assert\Range(min="0", max="1000")
     */
    private $triggeringPriority = 500;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $visibility = false;

    /**
     * @var Category|null
     *
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="badges")
     */
    private $category;

    /**
     * @ORM\ManyToMany(targetEntity=Volunteer::class, mappedBy="badges")
     */
    private $volunteers;

    /**
     * @var self|null
     *
     * @ORM\ManyToOne(targetEntity=Badge::class, inversedBy="children")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity=Badge::class, mappedBy="parent")
     */
    private $children;

    /**
     * @var self
     *
     * @ORM\ManyToOne(targetEntity=Badge::class, inversedBy="synonyms", cascade="all")
     */
    private $synonym;

    /**
     * @ORM\OneToMany(targetEntity=Badge::class, mappedBy="synonym", cascade="all")
     */
    private $synonyms;

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
        $this->volunteers = new ArrayCollection();
        $this->children   = new ArrayCollection();
        $this->synonyms   = new ArrayCollection();
    }

    public function getId() : ?int
    {
        return $this->id;
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

    public function getExternalId() : ?string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function isExternal() : bool
    {
        return null !== $this->externalId;
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

    public function getDescription() : ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description) : self
    {
        $this->description = $description;

        return $this;
    }

    public function getRenderingPriority() : ?int
    {
        return $this->renderingPriority;
    }

    public function setRenderingPriority(?int $renderingPriority) : self
    {
        $this->renderingPriority = $renderingPriority;

        return $this;
    }

    public function getTriggeringPriority() : int
    {
        return $this->triggeringPriority;
    }

    public function setTriggeringPriority(int $triggeringPriority) : Badge
    {
        $this->triggeringPriority = $triggeringPriority;

        return $this;
    }

    public function getVisibility() : ?bool
    {
        return $this->visibility;
    }

    public function setVisibility(bool $visibility) : self
    {
        $this->visibility = $visibility;

        return $this;
    }

    public function isVisible() : bool
    {
        return $this->visibility;
    }

    public function getCategory() : ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category) : self
    {
        $this->category = $category;

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
        if ($this->volunteers->contains($volunteer)) {
            $this->volunteers->removeElement($volunteer);
        }

        return $this;
    }

    public function getSynonym() : ?self
    {
        if ($this->synonym && $this->synonym->getSynonym()) {
            return $this->synonym->getSynonym();
        }

        return $this->synonym;
    }

    public function setSynonym(?self $synonym) : self
    {
        $this->synonym = $synonym;

        return $this;
    }

    public function getParent() : ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent) : self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildren() : Collection
    {
        return $this->children;
    }

    public function addChild(self $child) : self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child) : self
    {
        if ($this->children->contains($child)) {
            $this->children->removeElement($child);
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getFullName() : string
    {
        if ($this->description) {
            return sprintf('%s (%s)', $this->name, $this->description);
        }

        return $this->name;
    }

    /**
     * @return array
     */
    public function toSearchResults() : array
    {
        return [
            'id'   => (string) $this->getId(),
            'name' => $this->getFullName(),
        ];
    }

    public function __toString()
    {
        return $this->getFullName();
    }

    /**
     * @return Collection|self[]
     */
    public function getSynonyms() : Collection
    {
        return $this->synonyms;
    }

    public function addSynonym(self $synonym) : self
    {
        if (!$this->synonyms->contains($synonym)) {
            $this->synonyms[] = $synonym;
            $synonym->setSynonym($this);
        }

        return $this;
    }

    public function removeSynonym(self $synonym) : self
    {
        if ($this->synonyms->contains($synonym)) {
            $this->synonyms->removeElement($synonym);
            // set the owning side to null (unless already changed)
            if ($synonym->getSynonym() === $this) {
                $synonym->setSynonym(null);
            }
        }

        return $this;
    }

    public function canBeRemoved() : bool
    {
        if ($this->locked) {
            return false;
        }

        if ($this->parent || $this->children->count()) {
            return false;
        }

        return true;
    }

    public function getCoveringBadges(int $stop = null) : array
    {
        $parents = [];
        $ref     = $this->getParent();
        while ($ref && (null === $stop || $ref->getId() !== $stop)) {
            array_unshift($parents, $ref);
            $ref = $ref->getParent();
        }

        return $parents;
    }

    public function getCoveredBadges() : array
    {
        $children = [];

        foreach ($this->getChildren() as $child) {
            $children = array_merge($children, [$child], $child->getCoveredBadges());
        }

        return $children;
    }

    public function isUsable() : bool
    {
        if ($this->getSynonym()) {
            return false;
        }

        return true;
    }

    public function isEnabled() : bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled) : Badge
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function isLocked() : bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked) : Badge
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->getParent()) {
            // Infinite loop because parent was bound to one of his children
            if ($this->isParentLooping()) {
                $context
                    ->buildViolation('form.badge.errors.parent.loop', [
                        '%hierarchy%' => implode(' -> ', $this->getCoveringBadges($this->id)),
                    ])
                    ->atPath('parent')
                    ->addViolation();
            }

            // A parent cannot be a synonym
            if ($this->getParent()->getSynonym()) {
                $context
                    ->buildViolation('form.badge.errors.parent.synonym', [
                        '%name%' => $this->getParent()->getSynonym()->getName(),
                    ])
                    ->atPath('parent')
                    ->addViolation();
            }
        }

        foreach ($this->synonyms as $synonym) {
            /** @var Badge $synonym */

            // A badge cannot be marked as synonym if it is enabled
            if ($synonym->isVisible()) {
                $context
                    ->buildViolation('form.badge.errors.synonym.visible', [
                        '%name%' => $synonym->getName(),
                    ])
                    ->atPath('synonyms')
                    ->addViolation();
            }

            // A badge cannot be marked as synonym if it has synonyms himself
            if ($synonym->getSynonyms()->count()) {
                $context
                    ->buildViolation('form.badge.errors.synonym.has_synonyms', [
                        '%name%' => $synonym->getName(),
                    ])
                    ->atPath('synonyms')
                    ->addViolation();
            }

            // cannot have a synonym having parents
            if ($synonym->getParent()) {
                $context
                    ->buildViolation('form.badge.errors.synonym.has_parent', [
                        '%name%' => $synonym->getName(),
                    ])
                    ->atPath('synonyms')
                    ->addViolation();
            }
        }
    }

    private function isParentLooping() : bool
    {
        $ref = $this->getParent();
        while ($ref) {
            if ($ref->id === $this->id) {
                return true;
            }

            $ref = $ref->getParent();
        }

        return false;
    }

    private function isSynonymLooping() : bool
    {
        $ref = $this->getSynonym();
        while ($ref) {
            if ($ref->id === $this->id) {
                return true;
            }

            $ref = $ref->getSynonym();
        }

        return false;
    }
}

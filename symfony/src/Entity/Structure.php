<?php

namespace App\Entity;

use App\Contract\LockableInterface;
use App\Tools\EscapedArray;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StructureRepository")
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="pf_extid_idx", columns={"platform", "external_id"})
 *     },
 *     indexes={
 *         @ORM\Index(name="name_idx", columns={"name"})
 *     }
 * )
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Structure implements LockableInterface
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
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
     * @Assert\NotBlank
     * @Assert\Length(max="64")
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
     * @ORM\Column(type="string", length=32, nullable=true)
     * @Assert\Length(max="32")
     */
    private $shortcut;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $locked = false;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max="255")
     */
    private $president;

    /**
     * @var Volunteer[]
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Volunteer", mappedBy="structures")
     */
    private $volunteers;

    /**
     * @var Structure|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Structure", inversedBy="childrenStructures")
     */
    private $parentStructure;

    /**
     * @var Structure[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Structure", mappedBy="parentStructure")
     */
    private $childrenStructures;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastPegassUpdate;

    /**
     * @var User[]
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="structures")
     */
    private $users;

    /**
     * @var PrefilledAnswers[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\PrefilledAnswers", mappedBy="structure")
     */
    private $prefilledAnswers;

    /**
     * @ORM\OneToMany(targetEntity=VolunteerList::class, mappedBy="structure", cascade={"all"}, orphanRemoval=true)
     */
    private $volunteerLists;

    /**
     * @ORM\OneToMany(targetEntity=Template::class, mappedBy="structure", orphanRemoval=true)
     */
    private $templates;

    public function __construct()
    {
        $this->volunteers         = new ArrayCollection();
        $this->childrenStructures = new ArrayCollection();
        $this->users              = new ArrayCollection();
        $this->prefilledAnswers   = new ArrayCollection();
        $this->volunteerLists     = new ArrayCollection();
        $this->templates          = new ArrayCollection();
    }

    public function getShortcut() : ?string
    {
        return $this->shortcut;
    }

    public function setShortcut(?string $shortcut) : Structure
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    public function isLocked() : bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked) : Structure
    {
        $this->locked = $locked;

        return $this;
    }

    public function getDisplayName() : string
    {
        return mb_strtoupper($this->name);
    }

    public function getVolunteer(string $externalId) : ?Volunteer
    {
        foreach ($this->getVolunteers() as $volunteer) {
            if ($volunteer->getExternalId() === $externalId) {
                return $volunteer;
            }
        }

        return null;
    }

    /**
     * @return Collection|Volunteer[]
     */
    public function getVolunteers(bool $onlyEnabled = true) : Collection
    {
        if ($onlyEnabled) {
            return $this->getEnabledVolunteers();
        }

        return $this->volunteers->filter(function (Volunteer $volunteer) {
            return $this->platform === $volunteer->getPlatform();
        });
    }

    public function getExternalId() : string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : Structure
    {
        $this->externalId = $externalId;

        return $this;
    }

    /**
     * @return Collection|Volunteer[]
     */
    public function getEnabledVolunteers() : Collection
    {
        return $this->volunteers->filter(function (Volunteer $volunteer) {
            return $this->platform === $volunteer->getPlatform() && $volunteer->isEnabled();
        });
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

    /**
     * @return bool|null
     */
    public function isEnabled() : bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return Structure
     */
    public function setEnabled(bool $enabled) : self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return Structure
     */
    public function addVolunteer(Volunteer $volunteer) : self
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
    public function removeVolunteer(Volunteer $volunteer) : self
    {
        if ($this->volunteers->contains($volunteer)) {
            $this->volunteers->removeElement($volunteer);
            $volunteer->removeStructure($this);
        }

        return $this;
    }

    /**
     * @return Structure[]
     */
    public function getAncestors() : array
    {
        if (!$this->parentStructure) {
            return [];
        }

        return array_merge([$this->parentStructure], $this->parentStructure->getAncestors());
    }

    /**
     * @return Collection|self[]
     */
    public function getChildrenStructures() : Collection
    {
        return $this->childrenStructures;
    }

    /**
     * @param self $childrenStructure
     *
     * @return Structure
     */
    public function addChildrenStructure(self $childrenStructure) : self
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
    public function removeChildrenStructure(self $childrenStructure) : self
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
     * @return Structure|null
     */
    public function getParentStructure() : ?self
    {
        return $this->parentStructure;
    }

    /**
     * @param self|null $parentStructure
     *
     * @return Structure
     */
    public function setParentStructure(?self $parentStructure) : self
    {
        $this->parentStructure = $parentStructure;

        return $this;
    }

    public function hasChildren() : bool
    {
        return (bool) $this->childrenStructures->count();
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getLastPegassUpdate() : ?DateTimeInterface
    {
        return $this->lastPegassUpdate;
    }

    /**
     * @param DateTimeInterface|null $lastPegassUpdate
     *
     * @return Structure
     */
    public function setLastPegassUpdate(?DateTimeInterface $lastPegassUpdate) : self
    {
        $this->lastPegassUpdate = $lastPegassUpdate;

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getUsers() : Collection
    {
        return $this->users->filter(function (User $user) {
            return $this->platform === $user->getPlatform();
        });
    }

    public function addUser(User $user) : self
    {
        if (!$this->users->contains($user)) {
            $this->users[] = $user;
            $user->addStructure($this);
        }

        return $this;
    }

    public function removeUser(User $user) : self
    {
        if ($this->users->contains($user)) {
            $this->users->removeElement($user);
            $user->removeStructure($this);
        }

        return $this;
    }

    /**
     * @return Collection|PrefilledAnswers[]
     */
    public function getPrefilledAnswers() : Collection
    {
        return $this->prefilledAnswers;
    }

    public function addPrefilledAnswer(PrefilledAnswers $prefilledAnswer) : self
    {
        if (!$this->prefilledAnswers->contains($prefilledAnswer)) {
            $this->prefilledAnswers->add($prefilledAnswer);
            $prefilledAnswer->setStructure($this);
        }

        return $this;
    }

    public function removePrefilledAnswer(PrefilledAnswers $prefilledAnswers) : self
    {
        if ($this->prefilledAnswers->contains($prefilledAnswers)) {
            $this->prefilledAnswers->remove($prefilledAnswers);
            $prefilledAnswers->setStructure(null);
        }

        return $this;
    }

    /**
     * @return Volunteer|null
     */
    public function getPresidentVolunteer() : ?Volunteer
    {
        foreach ($this->getVolunteers() as $volunteer) {
            if ($volunteer->getExternalId() === $this->getPresident()) {
                return $volunteer;
            }
        }

        return null;
    }

    /**
     * @return string|null
     */
    public function getPresident() : ?string
    {
        return $this->president;
    }

    /**
     * @param string|null $president
     *
     * @return Structure
     */
    public function setPresident(?string $president) : self
    {
        $this->president = $president;

        return $this;
    }

    /**
     * @return DateTime|null
     *
     * @throws Exception
     */
    public function getNextPegassUpdate() : ?DateTime
    {
        if (!$this->lastPegassUpdate) {
            return null;
        }

        // Doctrine loaded an UTC-saved date using the default timezone (Europe/Paris)
        $utc      = (new DateTime($this->lastPegassUpdate->format('Y-m-d H:i:s'), new DateTimeZone('UTC')));
        $interval = new DateInterval(sprintf('PT%dS', Pegass::TTL[Pegass::TYPE_STRUCTURE] * 24 * 60 * 60));

        $nextPegassUpdate = clone $utc;
        $nextPegassUpdate->add($interval);

        return $nextPegassUpdate;
    }

    /**
     * @return array
     */
    public function toSearchResults() : array
    {
        return (new EscapedArray([
            'id'   => (string) $this->getId(),
            'name' => $this->getDisplayName(),
        ]))->getArrayCopy();
    }

    /**
     * @return int|null
     */
    public function getId() : ?int
    {
        return $this->id;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if ($this->isParentLooping()) {
            $context
                ->buildViolation('form.structure.parent.loop', [
                    '%hierarchy%' => implode(' -> ', $this->getParentHierarchy($this->id)),
                ])
                ->setInvalidValue($this->getDisplayName())
                ->atPath('parentStructure')
                ->addViolation();
        }
    }

    private function isParentLooping() : bool
    {
        $ref = $this->getParentStructure();
        while ($ref) {
            if ($ref->id === $this->id) {
                return true;
            }

            $ref = $ref->getParentStructure();
        }

        return false;
    }

    public function getParentHierarchy(int $stop = null) : array
    {
        $parents = [$this];

        $ref = $this->getParentStructure();
        if (!$ref) {
            return $parents;
        }

        do {
            array_unshift($parents, $ref);
            $ref = $ref->getParentStructure();
            if ($ref && $ref->getId() === $stop) {
                array_unshift($parents, $ref);
                break;
            }
        } while ($ref);

        return $parents;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDisplayName();
    }

    public function addVolunteerList(VolunteerList $volunteerList) : self
    {
        if (!$this->volunteerLists->contains($volunteerList)) {
            $this->volunteerLists[] = $volunteerList;
            $volunteerList->setStructure($this);
        }

        return $this;
    }

    public function removeVolunteerList(VolunteerList $volunteerList) : self
    {
        if ($this->volunteerLists->removeElement($volunteerList)) {
            // set the owning side to null (unless already changed)
            if ($volunteerList->getStructure() === $this) {
                $volunteerList->setStructure(null);
            }
        }

        return $this;
    }

    public function getVolunteerList(string $name) : ?VolunteerList
    {
        foreach ($this->getVolunteerLists() as $volunteerList) {
            if ($volunteerList->getName() === $name) {
                return $volunteerList;
            }
        }

        return null;
    }

    /**
     * @return Collection|VolunteerList[]
     */
    public function getVolunteerLists() : Collection
    {
        return $this->volunteerLists;
    }

    /**
     * @return null|string
     */
    public function getName() : ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Structure
     */
    public function setName(string $name) : self
    {
        $this->name = mb_strtoupper($name);

        return $this;
    }

    /**
     * @return Collection<int, Template>
     */
    public function getTemplates() : Collection
    {
        return $this->templates;
    }

    public function addTemplate(Template $template) : self
    {
        if (!$this->templates->contains($template)) {
            $this->templates[] = $template;
            $template->setStructure($this);
        }

        return $this;
    }

    public function removeTemplate(Template $template) : self
    {
        if ($this->templates->removeElement($template)) {
            // set the owning side to null (unless already changed)
            if ($template->getStructure() === $this) {
                $template->setStructure(null);
            }
        }

        return $this;
    }

    public function isEqualTo(Structure $structure)
    {
        return $this->id === $structure->getId();
    }
}

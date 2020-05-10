<?php

namespace App\Entity;

use App\Tools\PhoneNumberParser;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @ORM\Table(indexes={
 *     @ORM\Index(name="nivolx", columns={"nivol"}),
 *     @ORM\Index(name="phone_numberx", columns={"phone_number"}),
 *     @ORM\Index(name="emailx", columns={"email"}),
 *     @ORM\Index(name="enabledx", columns={"id", "enabled"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\VolunteerRepository")
 * @UniqueEntity("nivol")
 * @UniqueEntity("phoneNumber")
 * @UniqueEntity("email")
 */
class Volunteer
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
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    private $identifier;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, unique=true)
     * @Assert\NotNull
     * @Assert\NotBlank
     * @Assert\Length(max=80)
     */
    private $nivol;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\NotNull
     * @Assert\NotBlank
     * @Assert\Length(max=80)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\NotNull
     * @Assert\NotBlank
     * @Assert\Length(max=80)
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Length(max=20)
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\Length(max=80)
     * @Assert\Email
     */
    private $email;

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

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $minor = false;

    /**
     * @var array
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Tag", inversedBy="volunteers")
     */
    private $tags;

    /**
     * Same as $tags but only contain the highest tags in the
     * tag hierarchy, to avoid flooding UX of skills that
     * wrap other ones.
     *
     * @var array
     */
    private $tagsView;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastPegassUpdate;

    /**
     * @var array
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $report;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Structure", inversedBy="volunteers")
     */
    private $structures;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Communication", mappedBy="volunteer")
     */
    private $communications;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\UserInformation", mappedBy="volunteer")
     */
    private $userInformation;

    public function __construct()
    {
        $this->tags       = new ArrayCollection();
        $this->structures = new ArrayCollection();
        $this->communications = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return Volunteer
     */
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;

        return $this;
    }

    /**
     * @return string
     */
    public function getNivol(): ?string
    {
        return $this->nivol;
    }

    /**
     * @param string $nivol
     *
     * @return Volunteer
     */
    public function setNivol(string $nivol): self
    {
        $this->nivol = $nivol;

        return $this;
    }

    /**
     * @return string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string|null $phoneNumber
     *
     * @return $this
     */
    public function setPhoneNumber(?string $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @param ExecutionContextInterface $context
     * @param                           $payload
     * @Assert\Callback()
     */
    public function validatePhoneNumber(ExecutionContextInterface $context, $payload)
    {
        if (null === PhoneNumberParser::parse($this->phoneNumber)) {
            $context->buildViolation('This value is not valid.')
                    ->atPath('phoneNumber')
                    ->addViolation();
        }
    }

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Volunteer
     */
    public function setEmail(?string $email): Volunteer
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return Volunteer
     */
    public function setEnabled(bool $enabled): Volunteer
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isLocked(): ?bool
    {
        return $this->locked;
    }

    /**
     * @param bool $locked
     *
     * @return Volunteer
     */
    public function setLocked(bool $locked): Volunteer
    {
        $this->locked = $locked;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMinor(): ?bool
    {
        return $this->minor;
    }

    /**
     * @param bool $minor
     *
     * @return Volunteer
     */
    public function setMinor(bool $minor): Volunteer
    {
        $this->minor = $minor;

        return $this;
    }

    /**
     * @return ArrayCollection
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        if (!$this->hasTag($tag)) {
            $this->tags->add($tag);
        }
    }

    /**
     * @param Tag $tag
     */
    public function removeTag(Tag $tag)
    {
        $this->tags->removeElement($tag);
    }

    /**
     * @return array
     */
    public function getTagsView(): array
    {
        if ($this->tagsView) {
            return $this->tagsView;
        }

        $this->tagsView = [];
        foreach ($this->tags->toArray() as $tag) {
            $this->tagsView[$tag->getLabel()] = $tag;
        }

        foreach (Tag::getTagHierarchyMap() as $masterTag => $tagsToRemove) {
            if (array_key_exists($masterTag, $this->tagsView)) {
                foreach ($tagsToRemove as $tagToRemove) {
                    if (array_key_exists($tagToRemove, $this->tagsView)) {
                        unset($this->tagsView[$tagToRemove]);
                    }
                }
            }
        }

        $this->tagsView = array_values($this->tagsView);

        return $this->tagsView;
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function hasTag(string $tagToSearch): bool
    {
        foreach ($this->tags as $tag) {
            if ($tag->getLabel() == $tagToSearch) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getFormattedPhoneNumber(): string
    {
        return chunk_split(sprintf('0%s', substr($this->getPhoneNumber(), 2)), 2, ' ');
    }

    /**
     * @return int
     */
    public function getTagPriority(): int
    {
        $highest = -1;
        foreach ($this->getTags() as $tag) {
            /* @var Tag $tag */
            if ($tag->getTagPriority() > $highest) {
                $highest = $tag->getTagPriority();
            }
        }

        return $highest;
    }

    /**
     * @return DateTime
     */
    public function getLastPegassUpdate(): ?DateTime
    {
        return $this->lastPegassUpdate;
    }

    /**
     * @param DateTime $lastPegassUpdate
     *
     * @return Volunteer
     */
    public function setLastPegassUpdate(DateTime $lastPegassUpdate): Volunteer
    {
        $this->lastPegassUpdate = $lastPegassUpdate;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getReport(): ?array
    {
        return $this->report ? json_decode($this->report, true) : null;
    }

    /**
     * @param array $report
     *
     * @return Volunteer
     */
    public function setReport(array $report): Volunteer
    {
        $this->report = json_encode($report, JSON_PRETTY_PRINT);

        return $this;
    }

    /**
     * @return bool
     */
    public function isCallable(): bool
    {
        return $this->enabled && ($this->phoneNumber || $this->email);
    }

    /**
     * @param string $message
     */
    public function addReport(string $message)
    {
        $report       = $this->getReport() ?? [];
        $report[]     = $message;
        $this->setReport($report);
    }

    /**
     * @return Collection|Structure[]
     */
    public function getStructures(): Collection
    {
        return $this->structures;
    }

    public function addStructure(Structure $structure): self
    {
        if (!$this->structures->contains($structure)) {
            $this->structures[] = $structure;
        }

        return $this;
    }

    public function removeStructure(Structure $structure): self
    {
        if ($this->structures->contains($structure)) {
            $this->structures->removeElement($structure);
        }

        return $this;
    }

    /**
     * @param TranslatorInterface $translator
     *
     * @return array
     */
    public function toSearchResults(TranslatorInterface $translator)
    {
        return [
            'nivol'      => strval($this->getNivol()),
            'firstName'  => $this->getFirstName(),
            'lastName'   => $this->getLastName(),
            'tags'       => $this->getTagsView() ? sprintf('(%s)', implode(', ', array_map(function (Tag $tag) use (
                $translator
            ) {
                return $translator->trans(sprintf('tag.shortcuts.%s', $tag->getLabel()));
            }, $this->getTagsView()))) : '',
            'structures' => sprintf('<br/>%s',
                implode('<br/>', array_map(function (Structure $structure) {
                    return $structure->getName();
                }, $this->getStructures()->toArray()))
            ),
        ];
    }

    /**
     * @return DateTime|null
     *
     * @throws Exception
     */
    public function getNextPegassUpdate(): ?DateTime
    {
        if (!$this->lastPegassUpdate) {
            return null;
        }

        // Doctrine loaded an UTC-saved date using the default timezone (Europe/Paris)
        $utc      = (new DateTime($this->lastPegassUpdate->format('Y-m-d H:i:s'), new DateTimeZone('UTC')));
        $interval = new DateInterval(sprintf('PT%dS', Pegass::TTL[Pegass::TYPE_STRUCTURE]));

        $nextPegassUpdate = clone $utc;
        $nextPegassUpdate->add($interval);

        return $nextPegassUpdate;
    }

    /**
     * @return bool
     *
     * @throws Exception
     */
    public function canForcePegassUpdate(): bool
    {
        if (!$this->lastPegassUpdate) {
            return true;
        }

        // Doctrine loaded an UTC-saved date using the default timezone (Europe/Paris)
        $utc = (new DateTime($this->lastPegassUpdate->format('Y-m-d H:i:s'), new DateTimeZone('UTC')));

        // Can happen when update dates are spread on a larger timeframe
        // See: PegassManager:spreadUpdateDatesInTTL()
        if ($utc->getTimestamp() > time()) {
            return true;
        }

        // Prevent several updates in less than 1h
        return time() - $utc->getTimestamp() > 3600;
    }

    public function getDisplayName()
    {
        if ($this->firstName && $this->lastName) {
            return sprintf('%s %s', $this->toName($this->firstName), $this->toName($this->lastName));
        }

        return sprintf('#%s', mb_strtoupper($this->nivol));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getDisplayName();
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function toName(string $name): string
    {
        return preg_replace_callback('/[^\\s\-]+/ui', function (array $match) {
            return sprintf("%s%s", mb_strtoupper(mb_substr($match[0], 0, 1)), mb_strtolower(mb_substr($match[0], 1)));
        }, $name);
    }

    /**
     * @return Collection|Communication[]
     */
    public function getCommunications(): Collection
    {
        return $this->communications;
    }

    public function addCommunication(Communication $communication): self
    {
        if (!$this->communications->contains($communication)) {
            $this->communications[] = $communication;
            $communication->setVolunteer($this);
        }

        return $this;
    }

    public function removeCommunication(Communication $communication): self
    {
        if ($this->communications->contains($communication)) {
            $this->communications->removeElement($communication);
            // set the owning side to null (unless already changed)
            if ($communication->getVolunteer() === $this) {
                $communication->setVolunteer(null);
            }
        }

        return $this;
    }

    public function getUserInformation()
    {
        return $this->userInformation;
    }

    public function setUserInformation($userInformation)
    {
        $this->userInformation = $userInformation;
        return $this;
    }
}

<?php

namespace App\Entity;

use App\Contract\LockableInterface;
use App\Tools\EscapedArray;
use DateInterval;
use DateTime;
use DateTimeZone;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="pf_extid_idx", columns={"platform", "external_id"})
 *     },
 *     indexes={
 *         @ORM\Index(name="emailx", columns={"email"}),
 *         @ORM\Index(name="internal_emailx", columns={"internal_email"}),
 *         @ORM\Index(name="enabledx", columns={"enabled"}),
 *         @ORM\Index(name="phone_number_optinx", columns={"phone_number_optin"}),
 *         @ORM\Index(name="email_optinx", columns={"email_optin"}),
 *         @ORM\Index(name="optout_untilx", columns={"optout_until"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\VolunteerRepository")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Volunteer implements LockableInterface
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
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\NotBlank
     * @Assert\Length(max=80)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\NotBlank
     * @Assert\Length(max=80)
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\Length(max=80)
     * @Assert\Email
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
     * @Assert\Length(max=80)
     * @Assert\Email
     */
    private $internalEmail;

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
     * @ORM\Column(type="boolean")
     */
    private $minor = false;

    /**
     * @var \DateTimeInterface
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
     * @var User
     *
     * @ORM\OneToOne(targetEntity="App\Entity\User", mappedBy="volunteer")
     */
    private $user;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $phoneNumberLocked = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $emailLocked = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 1})
     */
    private $phoneNumberOptin = true;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 1})
     */
    private $emailOptin = true;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="volunteer", cascade={"persist"})
     * @ORM\OrderBy({"communication" = "DESC"})
     */
    private $messages;

    /**
     * @ORM\ManyToMany(targetEntity=Phone::class, mappedBy="volunteers", orphanRemoval=true, cascade={"all"})
     * @ORM\OrderBy({"preferred" = "DESC"})
     *
     * @Assert\Valid
     */
    private $phones;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 0})
     */
    private $onlyOutboundSms = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default" : 1})
     */
    private $supportsShortCode = true;

    /**
     * @ORM\ManyToMany(targetEntity=Badge::class, inversedBy="volunteers")
     */
    private $badges;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $optoutUntil;

    /**
     * @ORM\ManyToMany(targetEntity=VolunteerList::class, mappedBy="volunteers")
     */
    private $lists;

    public function __construct()
    {
        $this->structures = new ArrayCollection();
        $this->messages   = new ArrayCollection();
        $this->phones     = new ArrayCollection();
        $this->badges     = new ArrayCollection();
        $this->lists      = new ArrayCollection();
    }

    public function getFullname() : string
    {
        return $this->firstName.' '.$this->lastName;;
    }

    public function getPhoneNumber() : ?string
    {
        $phone = $this->getPhone();

        return $phone ? $phone->getE164() : null;
    }

    public function getPhone() : ?Phone
    {
        foreach ($this->getPhones() as $phone) {
            if ($phone->isPreferred()) {
                return $phone;
            }
        }

        return null;
    }

    /**
     * @return Phone[]
     */
    public function getPhones() : Collection
    {
        return $this->phones;
    }

    public function getPhoneByNumber(string $e164) : ?Phone
    {
        foreach ($this->getPhones() as $phone) {
            if ($phone->getE164() === $e164) {
                return $phone;
            }
        }

        return null;
    }

    public function hasPhoneNumber(string $phoneNumber) : bool
    {
        foreach ($this->phones as $phone) {
            /** @var Phone $phone */
            if ($phoneNumber === $phone->getE164()) {
                return true;
            }
        }

        return false;
    }

    public function getEmail() : ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email) : Volunteer
    {
        $this->email = $email;

        return $this;
    }

    public function getInternalEmail() : ?string
    {
        return $this->internalEmail;
    }

    public function setInternalEmail(?string $internalEmail) : Volunteer
    {
        $this->internalEmail = $internalEmail;

        return $this;
    }

    public function isLocked() : ?bool
    {
        return $this->locked;
    }

    public function setLocked(bool $locked) : Volunteer
    {
        $this->locked = $locked;

        return $this;
    }

    public function getDisplayName() : string
    {
        if ($this->firstName && $this->lastName) {
            return sprintf('%s %s', $this->toName($this->firstName), $this->toName($this->lastName));
        }

        return sprintf('#%s', mb_strtoupper($this->externalId));
    }

    public function isMinor() : bool
    {
        return $this->minor;
    }

    public function setMinor(bool $minor) : void
    {
        $this->minor = $minor;
    }

    public function getFormattedPhoneNumber() : ?string
    {
        return $this->getPhone() ? $this->getPhone()->getNational() : null;
    }

    public function getLastPegassUpdate() : ?\DateTimeInterface
    {
        return $this->lastPegassUpdate;
    }

    public function setLastPegassUpdate(\DateTimeInterface $lastPegassUpdate) : Volunteer
    {
        $this->lastPegassUpdate = $lastPegassUpdate;

        return $this;
    }

    public function isCallable() : bool
    {
        $hasPhone = $this->getPhone() && $this->phoneNumberOptin;
        $hasEmail = $this->email && $this->emailOptin;
        $isOptin  = !$this->optoutUntil || $this->optoutUntil->getTimestamp() < time();

        return $this->enabled && $isOptin && ($hasPhone || $hasEmail);
    }

    public function addReport(string $message)
    {
        $report   = $this->getReport() ?? [];
        $report[] = $message;
        $this->setReport($report);
    }

    public function getReport() : ?array
    {
        return $this->report ? json_decode($this->report, true) : null;
    }

    public function setReport(array $report) : Volunteer
    {
        $this->report = json_encode($report, JSON_PRETTY_PRINT);

        return $this;
    }

    public function getStructureIds() : array
    {
        return array_map(function (Structure $structure) {
            return $structure->getId();
        }, $this->getStructures()->toArray());
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
    public function setId(int $id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Structure[]
     */
    public function getStructures(bool $onlyEnabled = true) : Collection
    {
        if ($onlyEnabled) {
            return $this->getEnabledStructures();
        }

        return $this->structures->filter(function (Structure $structure) {
            return $this->platform === $structure->getPlatform();
        });
    }

    public function getEnabledStructures() : Collection
    {
        return $this->structures->filter(function (Structure $structure) {
            return $this->platform === $structure->getPlatform() && $structure->isEnabled();
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

    public function isEnabled() : ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled) : Volunteer
    {
        $this->enabled = $enabled;

        return $this;
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

    /**
     * @param Structure[] $structures
     */
    public function syncStructures(array $newStructures)
    {
        $currentStructures = $this->structures->toArray();

        foreach ($newStructures as $structure) {
            if (!in_array($structure, $currentStructures)) {
                $this->structures->add($structure);
            }
        }

        foreach ($currentStructures as $structure) {
            if (!in_array($structure, $newStructures)) {
                $this->structures->removeElement($structure);
            }
        }
    }

    public function getMainStructure(bool $onlyEnabled = true) : ?Structure
    {
        /** @var Structure|null $mainStructure */
        $mainStructure = null;
        $structures    = $onlyEnabled ? $this->getStructures() : $this->structures;
        foreach ($structures as $structure) {
            /** @var Structure $structure */
            if (!$mainStructure || $structure->getExternalId() < $mainStructure->getExternalId()) {
                $mainStructure = $structure;
            }
        }

        return $mainStructure;
    }

    public function getExternalId() : string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : Volunteer
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function toSearchResults(?User $user = null)
    {
        $badges = implode(', ', array_map(function (Badge $badge) {
            return $badge->getName();
        }, $this->getVisibleBadges($user)));

        return (new EscapedArray([
            'id'          => strval($this->getId()),
            'external-id' => $this->getExternalId(),
            'human'       => sprintf('%s %s%s / %s', $this->getFirstName(), $this->getLastName(), $badges ? sprintf(' (%s)', $badges) : null, $this->getExternalId()),
        ]))->getArrayCopy();
    }

    public function getVisibleBadges(?User $user = null) : array
    {
        $favs = null;
        if (null !== $user) {
            $favs = $user->getSortedFavoriteBadges();
        }

        $badges = $this->getBadges()->toArray();

        // Only use synonyms
        foreach ($badges as $key => $badge) {
            /** @var Badge $badge */
            if ($badge->getSynonym() && !in_array($badge->getSynonym(), $badges)) {
                $badges[] = $badge->getSynonym();
                unset($badges[$key]);
            }
        }

        // Only use visible badges
        $badges = array_filter($badges, function (Badge $badge) use ($favs) {
            if ($favs) {
                foreach ($favs as $fav) {
                    if ($fav === $badge) {
                        return true;
                    }
                }

                return false;
            }

            return $badge->isVisible();
        });

        // Only rendering the higher badge in the hierarchy
        $badges = array_filter($badges, function (Badge $badge) use ($badges) {
            foreach ($badge->getChildren() as $child) {
                if (in_array($child, $badges)) {
                    return false;
                }
            }

            return true;
        });

        // Sorting badges by category's priority and then by priority
        usort($badges, [Badge::class, 'sortBadges']);

        return $badges;
    }

    public function getFirstName() : ?string
    {
        return $this->firstName;
    }

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName() : ?string
    {
        return $this->lastName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

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

    public function setBadges(array $badges)
    {
        $this->badges->clear();
        foreach ($badges as $badge) {
            $this->badges->add($badge);
        }
    }

    public function getEnabledBadges() : Collection
    {
        return $this->badges->filter(function (Badge $badge) {
            return $this->platform === $badge->getPlatform() && $badge->isEnabled();
        });
    }

    public function getNextPegassUpdate() : ?DateTime
    {
        if (!$this->lastPegassUpdate) {
            return null;
        }

        // Doctrine loaded an UTC-saved date using the default timezone (Europe/Paris)
        $utc      = (new DateTime($this->lastPegassUpdate->format('Y-m-d H:i:s'), new DateTimeZone('UTC')));
        $interval = new DateInterval(sprintf('PT%dS', Pegass::TTL[Pegass::TYPE_VOLUNTEER] * 24 * 60 * 60));

        $nextPegassUpdate = clone $utc;
        $nextPegassUpdate->add($interval);

        return $nextPegassUpdate;
    }

    public function getTruncatedName() : string
    {
        if ($this->firstName && $this->lastName) {
            return sprintf('%s %s', $this->toName($this->firstName), strtoupper(substr($this->lastName, 0, 1)));
        }

        return sprintf('#%s', mb_strtoupper($this->externalId));
    }

    private function toName(string $name) : string
    {
        return preg_replace_callback('/[^\\s\-]+/ui', function (array $match) {
            return sprintf("%s%s", mb_strtoupper(mb_substr($match[0], 0, 1)), mb_strtolower(mb_substr($match[0], 1)));
        }, $name);
    }

    public function __toString()
    {
        return $this->getDisplayName();
    }

    public function getUser() : ?User
    {
        if ($this->user && $this->user->isTrusted()) {
            return $this->user;
        }

        return null;
    }

    public function setUser(?User $user)
    {
        $this->user = $user;

        return $this;
    }

    public function isUserEnabled() : bool
    {
        return $this->user && $this->user->isVerified() && $this->user->isTrusted();
    }

    public function isPhoneNumberLocked() : ?bool
    {
        return $this->phoneNumberLocked;
    }

    public function setPhoneNumberLocked(bool $phoneNumberLocked) : self
    {
        $this->phoneNumberLocked = $phoneNumberLocked;

        return $this;
    }

    public function isEmailLocked() : ?bool
    {
        return $this->emailLocked;
    }

    public function setEmailLocked(bool $emailLocked) : self
    {
        $this->emailLocked = $emailLocked;

        return $this;
    }

    public function shouldBeLocked(Volunteer $volunteer) : bool
    {
        $old = get_object_vars($volunteer);
        $new = get_object_vars($this);

        foreach ($old as $key => $value) {
            if ('phone' === $key || 'email' === $key || 'structures' === $key || 'user' === $key) {
                continue;
            }

            if ($old[$key] !== $new[$key]) {
                return true;
            }
        }

        return false;
    }

    public function getHiddenPhone() : ?string
    {
        if (null === ($phone = $this->getPhone())) {
            return null;
        }

        return $phone->getHidden();
    }

    public function getHiddenEmail() : ?string
    {
        if (null === $this->email) {
            return null;
        }

        $username = substr($this->email, 0, strrpos($this->email, '@'));
        $domain   = substr($this->email, strrpos($this->email, '@') + 1);

        return substr($username, 0, 1).str_repeat('*', max(strlen($username) - 2, 0)).substr($username, -1).'@'.$domain;
    }

    public function isPhoneNumberOptin() : ?bool
    {
        return $this->phoneNumberOptin;
    }

    public function setPhoneNumberOptin(bool $phoneNumberOptin) : self
    {
        $this->phoneNumberOptin = $phoneNumberOptin;

        return $this;
    }

    public function isEmailOptin() : ?bool
    {
        return $this->emailOptin;
    }

    public function setEmailOptin(bool $emailOptin) : self
    {
        $this->emailOptin = $emailOptin;

        return $this;
    }

    public function getMessages() : Collection
    {
        return $this->messages;
    }

    /**
     * @Assert\Callback()
     */
    public function doNotDisableRedCallUsers(ExecutionContextInterface $context, $payload)
    {
        if ($this->user && !$this->enabled) {
            $context->buildViolation('form.volunteer.errors.redcall_user')
                    ->atPath('enabled')
                    ->addViolation();
        }
    }

    public function clearPhones()
    {
        $this->phones->clear();
    }

    public function isOnlyOutboundSms() : bool
    {
        return $this->onlyOutboundSms;
    }

    public function setOnlyOutboundSms(bool $onlyOutboundSms) : Volunteer
    {
        $this->onlyOutboundSms = $onlyOutboundSms;

        return $this;
    }

    public function isSupportsShortCode() : bool
    {
        return $this->supportsShortCode;
    }

    public function setSupportsShortCode(bool $supportsShortCode) : Volunteer
    {
        $this->supportsShortCode = $supportsShortCode;

        return $this;
    }

    public function removeBadge(Badge $badge) : self
    {
        if ($this->badges->contains($badge)) {
            $this->badges->removeElement($badge);
            $badge->removeVolunteer($this);
        }

        return $this;
    }

    /**
     * @return Collection|VolunteerList[]
     */
    public function getLists() : Collection
    {
        return $this->lists;
    }

    public function addList(VolunteerList $list) : self
    {
        if (!$this->lists->contains($list)) {
            $this->lists[] = $list;
        }

        return $this;
    }

    public function removeList(VolunteerList $list) : self
    {
        if ($this->lists->contains($list)) {
            $this->lists->removeElement($list);
            $list->removeVolunteer($this);
        }

        return $this;
    }

    public function removeExpiredBadges()
    {
        $toRemove = [];
        foreach ($this->badges as $badge) {
            /** @var Badge $badge */
            if ($badge->hasExpired()) {
                $toRemove[] = $badge;
            }
        }
        foreach ($toRemove as $badge) {
            $this->badges->removeElement($badge);
        }
    }

    public function hasBadge(string $platform, string $badgeName) : bool
    {
        foreach ($this->badges as $badge) {
            /** @var Badge $badge */
            if ($platform === $badge->getPlatform() && $badgeName === $badge->getName()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Badge[] $badges
     */
    public function setExternalBadges(array $badges)
    {
        $toKeep = [];
        foreach ($this->badges as $badge) {
            /** @var Badge $badge */
            if (!$badge->isExternal()) {
                $toKeep[] = $badge;
            }
        }

        $this->badges->clear();

        foreach ($toKeep as $badge) {
            $this->addBadge($badge);
        }

        foreach ($badges as $badge) {
            $this->addBadge($badge);
        }
    }

    public function addBadge(Badge $badge) : self
    {
        if (!$this->badges->contains($badge)) {
            $this->badges[] = $badge;
        }

        return $this;
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context, $payload)
    {
        if (!$this->getPhones()->count()) {
            return;
        }

        $main = 0;
        foreach ($this->getPhones() as $phone) {
            /** @var Phone $phone */
            if ($phone->isPreferred()) {
                $main++;
            }
        }

        if (0 === $main) {
            if (1 === $this->getPhones()->count()) {
                foreach ($this->getPhones() as $phone) {
                    $phone->setPreferred(true);
                }
            } else {
                $context->buildViolation('form.phone_card.error_no_preferred')
                        ->atPath('phones')
                        ->addViolation();
            }
        }

        if ($main > 1) {
            $context->buildViolation('form.phone_card.error_multi_preferred')
                    ->atPath('phones')
                    ->addViolation();
        }

        $phones = [];
        foreach ($this->getPhones() as $phone) {
            $phones[$phone->getE164()] = ($phones[$phone->getE164()] ?? 0) + 1;
        }
        foreach ($phones as $count) {
            if ($count > 1) {
                $context->buildViolation('form.phone_card.error_duplicate')
                        ->atPath('phones')
                        ->addViolation();
            }
        }
    }

    public function getBadgePriority(?User $user = null) : int
    {
        $lowest = 0xFFFFFFFF;

        foreach ($this->getVisibleBadges($user) as $badge) {
            /** @var Badge $badge */
            if ($badge->getRenderingPriority() < $lowest) {
                $lowest = $badge->getRenderingPriority();
            }
        }

        return $lowest;
    }

    public function getOptoutUntil() : ?\DateTimeInterface
    {
        return $this->optoutUntil;
    }

    public function setOptoutUntil(?\DateTimeInterface $optoutUntil) : self
    {
        $this->optoutUntil = $optoutUntil;

        return $this;
    }

    public function addPhoneAndEnsureOnlyOneIsPreferred(Phone $phone)
    {
        $this->addPhone($phone);

        $this->setPhoneAsPreferred($phone);
    }

    public function addPhone(Phone $phone) : self
    {
        if (!$this->phones->contains($phone)) {
            $this->phones[] = $phone;
            $phone->addVolunteer($this);
        }

        return $this;
    }

    public function setPhoneAsPreferred(Phone $phone)
    {
        if (!$this->phones->contains($phone)) {
            return;
        }

        foreach ($this->getPhones() as $current) {
            $current->setPreferred(
                $current->getE164() === $phone->getE164()
            );
        }
    }

    public function removePhoneAndEnsureOneIsPreferred(Phone $phone)
    {
        $this->removePhone($phone);

        $this->ensureOnePhoneIsPreferred();
    }

    public function removePhone(Phone $phone) : self
    {
        if ($this->phones->removeElement($phone)) {
            // set the owning side to null (unless already changed)
            foreach ($phone->getVolunteers() as $volunteer) {
                if ($volunteer === $this) {
                    $phone->removeVolunteer($this);
                }
            }
        }

        return $this;
    }

    public function ensureOnePhoneIsPreferred()
    {
        $main = 0;
        foreach ($this->getPhones() as $phone) {
            $main += (int) $phone->isPreferred();
        }

        if (0 === $main && $this->getPhones()->count() > 0) {
            $this->getPhones()->first()->setPreferred(true);
        }
    }

    public function needsShortcutInMessages() : bool
    {
        return $this->structures->count() > 1
               || $this->user && $this->user->getStructures()->count() > 1;
    }
}

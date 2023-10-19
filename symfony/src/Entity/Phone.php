<?php

namespace App\Entity;

use App\Contract\PhoneInterface;
use App\Repository\PhoneRepository;
use App\Validator\Constraints as CustomAssert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * @ORM\Entity(repositoryClass=PhoneRepository::class)
 * @ORM\Table(indexes={
 *     @ORM\Index(name="nationalx", columns={"national"}),
 *     @ORM\Index(name="internationalx", columns={"international"}),
 *     @ORM\Index(name="ismobilex", columns={"mobile"})
 * })
 * @ORM\HasLifecycleCallbacks()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 *
 * @CustomAssert\Phone
 */
class Phone implements PhoneInterface
{
    public const DEFAULT_LANG = 'FR';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity=Volunteer::class, inversedBy="phones")
     */
    private $volunteers;

    /**
     * @ORM\Column(type="boolean")
     */
    private $preferred = false;

    /**
     * @ORM\Column(type="string", length=2)
     */
    private $countryCode;

    /**
     * @ORM\Column(type="smallint")
     */
    private $prefix;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $e164;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $national;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $international;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $mobile = false;

    public function __construct()
    {
        $this->volunteers = new ArrayCollection();
    }

    public function getId() : ?int
    {
        return $this->id;
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

    public function isPreferred() : bool
    {
        return $this->preferred;
    }

    public function setPreferred(bool $preferred) : void
    {
        $this->preferred = $preferred;
    }

    public function getPrefix() : ?int
    {
        return $this->prefix;
    }

    public function setPrefix(int $prefix) : self
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function getE164() : ?string
    {
        return $this->e164;
    }

    public function setE164(string $e164) : self
    {
        $this->e164 = $e164;

        return $this;
    }

    public function getNational() : ?string
    {
        return $this->national;
    }

    public function setNational(string $national) : self
    {
        $this->national = $national;

        return $this;
    }

    public function getInternational() : ?string
    {
        return $this->international;
    }

    public function setInternational(string $international) : self
    {
        $this->international = $international;

        return $this;
    }

    public function getHidden() : ?string
    {
        $nationalNumber = $this->national;

        if (!$nationalNumber) {
            return null;
        }

        return substr($nationalNumber, 0, 4).str_repeat('*', strlen($nationalNumber) - 8).substr($nationalNumber, -4);
    }

    public function __toString() : string
    {
        return ''.$this->e164;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function onChange()
    {
        $this->populateFromE164();
    }

    public function populateFromE164()
    {
        if (!$this->e164) {
            return;
        }

        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsed    = $phoneUtil->parse($this->e164, self::DEFAULT_LANG);

        $this->setCountryCode($phoneUtil->getRegionCodeForCountryCode($parsed->getCountryCode()));
        $this->setPrefix($parsed->getCountryCode());
        $this->setNational($phoneUtil->format($parsed, PhoneNumberFormat::NATIONAL));
        $this->setInternational($phoneUtil->format($parsed, PhoneNumberFormat::INTERNATIONAL));
        $this->setMobile(PhoneNumberType::MOBILE === $phoneUtil->getNumberType($parsed));
    }

    public function getCountryCode() : ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(string $countryCode) : self
    {
        $this->countryCode = $countryCode;

        return $this;
    }

    public function isMobile() : ?bool
    {
        return $this->mobile;
    }

    public function setMobile(bool $mobile) : self
    {
        $this->mobile = $mobile;

        return $this;
    }
}

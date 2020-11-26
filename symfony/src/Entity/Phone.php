<?php

namespace App\Entity;

use App\Repository\PhoneRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PhoneRepository::class)
 * @ORM\Table(indexes={
 *     @ORM\Index(name="nationalx", columns={"national"}),
 *     @ORM\Index(name="internationalx", columns={"international"})
 * })
 */
class Phone
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Volunteer::class, inversedBy="phones")
     * @ORM\JoinColumn(nullable=false)
     */
    private $volunteer;

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
     * @ORM\Column(type="string", length=32, unique=true)
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

    public function getId() : ?int
    {
        return $this->id;
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

    public function isPreferred() : bool
    {
        return $this->preferred;
    }

    public function setPreferred(bool $preferred) : void
    {
        $this->preferred = $preferred;
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

    public function getHidden() : string
    {
        $nationalNumber = $this->national;

        return substr($nationalNumber, 0, 4).str_repeat('*', strlen($nationalNumber) - 8).substr($nationalNumber, -4);
    }
}

<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VolunteerImportRepository")
 */
class VolunteerImport
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $nivol;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $isMinor;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $phone;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $postalCode;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isCallable;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isImportable = true;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $tags;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $status;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     */
    public function setId(?int $id)
    {
        $this->id = $id;
    }

    /**
     * @return null|string
     */
    public function getNivol(): ?string
    {
        return $this->nivol;
    }

    /**
     * @param null|string $nivol
     *
     * @return VolunteerImport
     */
    public function setNivol(?string $nivol): self
    {
        $this->nivol = $nivol;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * @param null|string $firstName
     *
     * @return VolunteerImport
     */
    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * @param null|string $lastName
     *
     * @return VolunteerImport
     */
    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isMinor(): ?bool
    {
        return $this->isMinor;
    }

    /**
     * @param bool|null $isMinor
     *
     * @return VolunteerImport
     */
    public function setIsMinor(?bool $isMinor): self
    {
        $this->isMinor = $isMinor;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * @param null|string $phone
     *
     * @return VolunteerImport
     */
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * @param string|null $postalCode
     *
     * @return $this
     */
    public function setPostalCode(?string $postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param null|string $email
     *
     * @return VolunteerImport
     */
    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isCallable(): ?bool
    {
        return $this->isCallable;
    }

    /**
     * @param bool|null $isCallable
     *
     * @return VolunteerImport
     */
    public function setIsCallable(?bool $isCallable): self
    {
        $this->isCallable = $isCallable;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /**
     * @param array|null $tags
     *
     * @return VolunteerImport
     */
    public function setTags(?array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getStatus(): ?array
    {
        return $this->status;
    }

    /**
     * @param array|null $status
     *
     * @return VolunteerImport
     */
    public function setStatus(?array $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return bool
     */
    public function isImportable(): bool
    {
        return $this->isImportable;
    }

    /**
     * @param bool $isImportable
     *
     * @return VolunteerImport
     */
    public function setIsImportable(bool $isImportable): self
    {
        $this->isImportable = $isImportable;

        return $this;
    }

    /**
     * @param $message
     */
    public function addError(string $message)
    {
        $status             = $this->status ?? [];
        $status[]           = sprintf('ERROR (import failed): %s', $message);
        $this->status       = $status;
        $this->isImportable = false;
    }

    /**
     * @param $message
     */
    public function addWarning(string $message)
    {
        $status       = $this->status ?? [];
        $status[]     = sprintf('WARNING (partial import): %s', $message);
        $this->status = $status;
    }
}

<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(indexes={@ORM\Index(name="nivolx", columns={"nivol"})})
 * @ORM\Entity(repositoryClass="App\Repository\VolunteerRepository")
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
     * @ORM\Column(type="string", length=80, unique=true)
     */
    private $nivol;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80)
     */
    private $firstName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80)
     */
    private $lastName;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     */
    private $phoneNumber;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $postalCode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80, nullable=true)
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

    public function __construct()
    {
        $this->tags = new ArrayCollection();
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
    public function getNivol(): string
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
    public function getFirstName(): string
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
    public function getLastName(): string
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
     * @return string
     */
    public function getPhoneNumber(): string
    {
        return $this->phoneNumber;
    }

    /**
     * @param string $phoneNumber
     *
     * @return $this
     */
    public function setPhoneNumber(string $phoneNumber)
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    /**
     * @param string $postalCode
     *
     * @return Volunteer
     */
    public function setPostalCode(?string $postalCode): Volunteer
    {
        $this->postalCode = $postalCode;

        return $this;
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
    public function isEnabled(): bool
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
    public function isLocked(): bool
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
     * @return ArrayCollection
     */
    public function getTags(): Collection
    {
        return $this->tags;
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
}
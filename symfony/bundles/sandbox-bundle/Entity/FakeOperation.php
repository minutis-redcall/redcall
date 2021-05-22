<?php

namespace Bundles\SandboxBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Bundles\SandboxBundle\Repository\FakeOperationRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class FakeOperation
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
     * @var int
     *
     * @ORM\Column(type="integer")
     */
    private $structureExternalId;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $ownerEmail;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @var Collection}FakeOperationResource[]
     *
     * @ORM\OneToMany(targetEntity="Bundles\SandboxBundle\Entity\FakeOperationResource", mappedBy="operation",
     *                                                                                   cascade={"persist"})
     */
    private $resources;

    public function __construct()
    {
        $this->resources = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function setId(int $id) : FakeOperation
    {
        $this->id = $id;

        return $this;
    }

    public function getStructureExternalId() : int
    {
        return $this->structureExternalId;
    }

    public function setStructureExternalId(int $structureExternalId) : FakeOperation
    {
        $this->structureExternalId = $structureExternalId;

        return $this;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : FakeOperation
    {
        $this->name = $name;

        return $this;
    }

    public function getOwnerEmail() : string
    {
        return $this->ownerEmail;
    }

    public function setOwnerEmail(string $ownerEmail) : FakeOperation
    {
        $this->ownerEmail = $ownerEmail;

        return $this;
    }

    public function getUpdatedAt() : \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt) : FakeOperation
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection|FakeOperationResource[]
     */
    public function getResources() : Collection
    {
        return $this->resources;
    }

    public function addResource(FakeOperationResource $resource) : self
    {
        if (!$this->resources->contains($resource)) {
            $this->resources[] = $resource;
            $resource->setOperation($this);
        }

        return $this;
    }

    public function removeResource(FakeOperationResource $resource) : self
    {
        if ($this->resources->removeElement($resource)) {
            // set the owning side to null (unless already changed)
            if ($resource->getOperation() === $this) {
                $resource->setOperation(null);
            }
        }

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function onChange()
    {
        $this->updatedAt = new \DateTime();
    }
}

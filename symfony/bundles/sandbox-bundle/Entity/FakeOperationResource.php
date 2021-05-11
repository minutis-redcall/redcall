<?php

namespace Bundles\SandboxBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Bundles\SandboxBundle\Repository\FakeOperationResourceRepository")
 */
class FakeOperationResource
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
     * @var FakeOperation
     *
     * @ORM\ManyToOne(targetEntity="Bundles\SandboxBundle\Entity\FakeOperation", inversedBy="resources")
     */
    private $operation;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $volunteerExternalId;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): FakeOperationResource
    {
        $this->id = $id;

        return $this;
    }

    public function getOperation(): FakeOperation
    {
        return $this->operation;
    }

    public function setOperation(FakeOperation $operation): FakeOperationResource
    {
        $this->operation = $operation;

        return $this;
    }

    public function getVolunteerExternalId(): string
    {
        return $this->volunteerExternalId;
    }

    public function setVolunteerExternalId(string $volunteerExternalId): FakeOperationResource
    {
        $this->volunteerExternalId = $volunteerExternalId;

        return $this;
    }
}

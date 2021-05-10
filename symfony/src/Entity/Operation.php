<?php

namespace App\Entity;

use App\Repository\OperationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OperationRepository::class)
 */
class Operation
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $operationExternalId;

    /**
     * @ORM\OneToMany(targetEntity=Choice::class, mappedBy="operation")
     */
    private $choices;

    /**
     * @ORM\OneToOne(targetEntity=Campaign::class, mappedBy="operation", cascade={"persist", "remove"})
     */
    private $campaign;

    public function __construct()
    {
        $this->choices = new ArrayCollection();
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getOperationExternalId() : ?int
    {
        return $this->operationExternalId;
    }

    public function setOperationExternalId(int $operationExternalId) : self
    {
        $this->operationExternalId = $operationExternalId;

        return $this;
    }

    /**
     * @return Collection|Choice[]
     */
    public function getChoices() : Collection
    {
        return $this->choices;
    }

    public function addChoice(Choice $choice) : self
    {
        if (!$this->choices->contains($choice)) {
            $this->choices[] = $choice;
            $choice->setOperation($this);
        }

        return $this;
    }

    public function removeChoice(Choice $choice) : self
    {
        if ($this->choices->removeElement($choice)) {
            // set the owning side to null (unless already changed)
            if ($choice->getOperation() === $this) {
                $choice->setOperation(null);
            }
        }

        return $this;
    }

    public function getCampaign() : ?Campaign
    {
        return $this->campaign;
    }

    public function setCampaign(?Campaign $campaign) : self
    {
        // unset the owning side of the relation if necessary
        if ($campaign === null && $this->campaign !== null) {
            $this->campaign->setOperation(null);
        }

        // set the owning side of the relation if necessary
        if ($campaign !== null && $campaign->getOperation() !== $this) {
            $campaign->setOperation($this);
        }

        $this->campaign = $campaign;

        return $this;
    }
}

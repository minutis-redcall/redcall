<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=App\Repository\ReportRepository::class)
 */
class Report
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    private $type;

    /**
     * @ORM\Column(type="integer")
     */
    private $messageCount;

    /**
     * @ORM\Column(type="integer")
     */
    private $answerCount;

    /**
     * @ORM\Column(type="integer")
     */
    private $choiceCount;

    /**
     * @ORM\Column(type="integer")
     */
    private $bounceCount;

    /**
     * @ORM\Column(type="integer")
     */
    private $answerRatio;

    /**
     * @ORM\OneToMany(targetEntity=ReportRepartition::class, mappedBy="report", orphanRemoval=true)
     */
    private $repartitions;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\OneToOne(targetEntity=Communication::class, mappedBy="report", cascade={"persist", "remove"})
     */
    private $communication;

    public function __construct()
    {
        $this->repartitions = new ArrayCollection();
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getType() : ?string
    {
        return $this->type;
    }

    public function setType(string $type) : self
    {
        $this->type = $type;

        return $this;
    }

    public function getMessageCount() : ?int
    {
        return $this->messageCount;
    }

    public function setMessageCount(int $messageCount) : self
    {
        $this->messageCount = $messageCount;

        return $this;
    }

    public function getAnswerCount() : ?int
    {
        return $this->answerCount;
    }

    public function setAnswerCount(int $answerCount) : self
    {
        $this->answerCount = $answerCount;

        return $this;
    }

    public function getChoiceCount() : ?int
    {
        return $this->choiceCount;
    }

    public function setChoiceCount(int $choiceCount) : self
    {
        $this->choiceCount = $choiceCount;

        return $this;
    }

    public function getBounceCount() : ?int
    {
        return $this->bounceCount;
    }

    public function setBounceCount(int $bounceCount) : self
    {
        $this->bounceCount = $bounceCount;

        return $this;
    }

    public function getAnswerRatio() : ?int
    {
        return $this->answerRatio;
    }

    public function setAnswerRatio(int $answerRatio) : self
    {
        $this->answerRatio = $answerRatio;

        return $this;
    }

    /**
     * @return Collection|ReportRepartition[]
     */
    public function getRepartitions() : Collection
    {
        return $this->repartitions;
    }

    public function addCostRepartition(ReportRepartition $costRepartition) : self
    {
        if (!$this->repartitions->contains($costRepartition)) {
            $this->repartitions[] = $costRepartition;
            $costRepartition->setReport($this);
        }

        return $this;
    }

    public function removeCostRepartition(ReportRepartition $costRepartition) : self
    {
        if ($this->repartitions->removeElement($costRepartition)) {
            // set the owning side to null (unless already changed)
            if ($costRepartition->getReport() === $this) {
                $costRepartition->setReport(null);
            }
        }

        return $this;
    }

    public function getUpdatedAt() : ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt) : self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCommunication() : ?Communication
    {
        return $this->communication;
    }

    public function setCommunication(?Communication $communication) : self
    {
        // unset the owning side of the relation if necessary
        if ($communication === null && $this->communication !== null) {
            $this->communication->setReport(null);
        }

        // set the owning side of the relation if necessary
        if ($communication !== null && $communication->getReport() !== $this) {
            $communication->setReport($this);
        }

        $this->communication = $communication;

        return $this;
    }
}

<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=App\Repository\ReportRepository::class)
 * @ORM\HasLifecycleCallbacks()
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
    private $messageCount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $questionCount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $answerCount = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $exchangeCount = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $answerRatio;

    /**
     * @ORM\OneToMany(targetEntity=ReportRepartition::class, mappedBy="report", cascade={"persist", "remove"},
     *                                                       orphanRemoval=true)
     */
    private $repartitions;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $costs = '[]';

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

    public function getQuestionCount() : int
    {
        return $this->questionCount;
    }

    public function setQuestionCount(int $questionCount) : Report
    {
        $this->questionCount = $questionCount;

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

    public function getExchangeCount() : ?int
    {
        return $this->exchangeCount;
    }

    public function setExchangeCount(int $exchangeCount) : self
    {
        $this->exchangeCount = $exchangeCount;

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

    public function addRepartition(ReportRepartition $costRepartition) : self
    {
        if (!$this->repartitions->contains($costRepartition)) {
            $this->repartitions[] = $costRepartition;
            $costRepartition->setReport($this);
        }

        return $this;
    }

    public function removeRepartition(ReportRepartition $costRepartition) : self
    {
        if ($this->repartitions->removeElement($costRepartition)) {
            // set the owning side to null (unless already changed)
            if ($costRepartition->getReport() === $this) {
                $costRepartition->setReport(null);
            }
        }

        return $this;
    }


    public function getCosts() : ?array
    {
        if (!$this->costs) {
            return null;
        }

        return json_decode($this->costs, true);
    }

    public function setCosts(array $costs) : self
    {
        $this->costs = json_encode($costs);

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

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function onChange()
    {
        $this->updatedAt = new \DateTime();
    }
}

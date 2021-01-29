<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=App\Repository\ReportRepartitionRepository::class)
 */
class ReportRepartition
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=Structure::class)
     */
    private $structure;

    /**
     * @ORM\Column(type="integer")
     */
    private $ratio;

    /**
     * @ORM\ManyToOne(targetEntity=Report::class, inversedBy="repartitions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $report;

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
     * @ORM\Column(type="string", length=64)
     */
    private $costs = '[]';

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getStructure() : ?Structure
    {
        return $this->structure;
    }

    public function setStructure(?Structure $structure) : self
    {
        $this->structure = $structure;

        return $this;
    }

    public function getRatio() : ?int
    {
        return $this->ratio;
    }

    public function setRatio(int $ratio) : self
    {
        $this->ratio = $ratio;

        return $this;
    }

    public function getReport() : ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report) : self
    {
        $this->report = $report;

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

    public function getQuestionCount()
    {
        return $this->questionCount;
    }

    public function setQuestionCount($questionCount)
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
}

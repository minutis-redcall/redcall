<?php

namespace App\Entity;

use App\Repository\ReportCostRepartitionRepository;
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
    private $totalMessages;

    /**
     * @ORM\Column(type="integer")
     */
    private $shareMessages;

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

    public function getTotalMessages() : ?int
    {
        return $this->totalMessages;
    }

    public function setTotalMessages(int $totalMessages) : self
    {
        $this->totalMessages = $totalMessages;

        return $this;
    }

    public function getShareMessages() : ?int
    {
        return $this->shareMessages;
    }

    public function setShareMessages(int $shareMessages) : self
    {
        $this->shareMessages = $shareMessages;

        return $this;
    }
}

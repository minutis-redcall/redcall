<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=App\Repository\ReportRepartitionRepository::class)
 */
class ReportRepartition extends AbstractReport
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
     * @ORM\Column(type="float")
     */
    private $ratio;

    /**
     * @ORM\ManyToOne(targetEntity=Report::class, inversedBy="repartitions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $report;

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

    public function getRatio() : ?float
    {
        return $this->ratio;
    }

    public function setRatio(float $ratio) : self
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

<?php

namespace Bundles\ChartBundle\Entity;

use Bundles\ChartBundle\Repository\PageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=PageRepository::class)
 */
class StatPage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $priority;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\OneToMany(targetEntity=StatChart::class, mappedBy="page", orphanRemoval=true)
     */
    private $charts;

    public function __construct()
    {
        $this->charts = new ArrayCollection();
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
    }

    public function getPriority() : ?int
    {
        return $this->priority;
    }

    public function setPriority(int $priority) : self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getCreatedAt() : ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt) : self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Collection|StatChart[]
     */
    public function getCharts() : Collection
    {
        return $this->charts;
    }

    public function addChart(StatChart $chart) : self
    {
        if (!$this->charts->contains($chart)) {
            $this->charts[] = $chart;
            $chart->setPage($this);
        }

        return $this;
    }

    public function removeChart(StatChart $chart) : self
    {
        if ($this->charts->removeElement($chart)) {
            // set the owning side to null (unless already changed)
            if ($chart->getPage() === $this) {
                $chart->setPage(null);
            }
        }

        return $this;
    }
}

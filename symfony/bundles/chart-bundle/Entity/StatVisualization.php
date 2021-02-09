<?php

namespace Bundles\ChartBundle\Entity;

use Bundles\ChartBundle\Repository\VisualizationRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=VisualizationRepository::class)
 */
class StatVisualization
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
     * @ORM\ManyToOne(targetEntity=StatPage::class, inversedBy="charts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $page;

    /**
     * @ORM\ManyToOne(targetEntity=StatQuery::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $query;

    /**
     * @ORM\Column(type="text")
     */
    private $options;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

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

    public function getPage() : ?StatPage
    {
        return $this->page;
    }

    public function setPage(?StatPage $page) : self
    {
        $this->page = $page;

        return $this;
    }

    public function getQuery() : ?StatQuery
    {
        return $this->query;
    }

    public function setQuery(?StatQuery $query) : self
    {
        $this->query = $query;

        return $this;
    }

    public function getOptions() : ?string
    {
        return $this->options;
    }

    public function setOptions(string $options) : self
    {
        $this->options = $options;

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
}

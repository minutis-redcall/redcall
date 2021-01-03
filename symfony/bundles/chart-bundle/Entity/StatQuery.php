<?php

namespace Bundles\ChartBundle\Entity;

use Bundles\ChartBundle\Repository\QueryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=QueryRepository::class)
 */
class StatQuery
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
     * @ORM\Column(type="text")
     */
    private $query;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $context;

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

    public function getQuery() : ?string
    {
        return $this->query;
    }

    public function setQuery(string $query) : self
    {
        $this->query = $query;

        return $this;
    }

    public function getContext() : ?string
    {
        return $this->context;
    }

    public function setContext(?string $context) : self
    {
        $this->context = $context;

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

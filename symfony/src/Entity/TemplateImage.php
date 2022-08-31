<?php

namespace App\Entity;

use App\Repository\TemplateImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Uuid;

/**
 * @ORM\Entity(repositoryClass=TemplateImageRepository::class)
 */
class TemplateImage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=36)
     */
    private $uuid;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity=Template::class, inversedBy="images")
     * @ORM\JoinColumn(nullable=false)
     */
    private $template;

    public function __construct()
    {
        $this->uuid = Uuid::uuid4();
    }

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getUuid() : ?string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid) : self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getContent() : ?string
    {
        return $this->content;
    }

    public function setContent(string $content) : self
    {
        $this->content = $content;

        return $this;
    }

    public function getTemplate() : ?Template
    {
        return $this->template;
    }

    public function setTemplate(?Template $template) : self
    {
        $this->template = $template;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\TemplateRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TemplateRepository::class)
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class Template
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Structure
     *
     * @ORM\ManyToOne(targetEntity=Structure::class, inversedBy="templates")
     * @ORM\JoinColumn(nullable=false)
     */
    private $structure;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $type = Communication::TYPE_SMS;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    private $subject;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $answers = [];

    /**
     * @ORM\Column(type="integer")
     */
    private $priority = 0;

    /**
     * @ORM\Column(type="string", length=5)
     */
    private $language;

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

    public function getName() : ?string
    {
        return $this->name;
    }

    public function setName(string $name) : self
    {
        $this->name = $name;

        return $this;
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

    public function getSubject() : ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject) : self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody() : ?string
    {
        return $this->body;
    }

    public function setBody(string $body) : self
    {
        $this->body = $body;

        return $this;
    }

    public function getAnswers() : ?array
    {
        return $this->answers;
    }

    public function setAnswers(array $answers) : self
    {
        $this->answers = $answers;

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

    public function __toString() : string
    {
        return sprintf('[%s] %s', $this->structure->getShortcut(), $this->name);
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): self
    {
        $this->language = $language;

        return $this;
    }
}

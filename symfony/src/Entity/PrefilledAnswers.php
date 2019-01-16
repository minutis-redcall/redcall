<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PrefilledAnswersRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class PrefilledAnswers
{
    // Because simple_array type use [im|ex]plode using "," as delimiter,
    // we should "escape" commas on the PFAs
    const COMMA_REPLACEMENT = '#COM#';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank
     * @Assert\Length(max=255)
     */
    private $label;

    /**
     * @ORM\Column(type="simple_array", nullable=true)
     * @Assert\Choice({
     *     Campaign::TYPE_GREEN,
     *     Campaign::TYPE_LIGHT_ORANGE,
     *     Campaign::TYPE_DARK_ORANGE,
     *     Campaign::TYPE_RED
     * }, multiple = true)
     */
    private $colors = [];

    /**
     * @ORM\Column(type="simple_array")
     * @Assert\Count(min=1, max=10)
     */
    private $answers = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getColors(): ?array
    {
        return $this->colors;
    }

    public function setColors(?array $colors): self
    {
        $this->colors = $colors;

        return $this;
    }

    public function getAnswers(): ?array
    {
        return $this->answers;
    }

    public function setAnswers(array $answers): self
    {
        $this->answers = $answers;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->sanitizePFAs();
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->sanitizePFAs();
    }

    /**
     * @ORM\PostLoad
     */
    public function onPostLoad()
    {
        $this->restorePFAs();
    }

    private function sanitizePFAs()
    {
        foreach ($this->answers as $index => $answer) {
            $this->answers[$index] = str_replace(',', self::COMMA_REPLACEMENT, $answer);
        }
    }

    private function restorePFAs()
    {
        foreach ($this->answers as $index => $answer) {
            $this->answers[$index] = str_replace(self::COMMA_REPLACEMENT, ',', $answer);
        }
    }
}

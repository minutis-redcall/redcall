<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AnswerRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Answer
{
    /**
     * @var int
     *
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Message
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Message", inversedBy="answers")
     */
    private $message;

    /**
     * Body of the answer as text
     *
     * @var string
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $raw;

    /**
     * Date of the answer's reception
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $receivedAt;

    /**
     * Date of the answer's last update
     *
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private $unclear;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Choice")
     */
    private $choices;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $byAdmin;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $sentiment;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $magnitude;

    /**
     * Answer constructor.
     */
    public function __construct()
    {
        $this->choices = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Answer
     */
    public function setId(int $id) : Answer
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage() : Message
    {
        return $this->message;
    }

    /**
     * @param Message $message
     *
     * @return $this
     */
    public function setMessage(Message $message) : Answer
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getRaw() : string
    {
        return $this->raw;
    }

    /**
     * @param string $raw
     *
     * @return Answer
     */
    public function setRaw(string $raw) : Answer
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * @return string
     */
    public function getSafeRaw() : string
    {
        return htmlentities($this->raw);
    }

    /**
     * @return DateTime
     */
    public function getReceivedAt() : DateTime
    {
        return $this->receivedAt;
    }

    /**
     * @param DateTime $receivedAt
     *
     * @return Answer
     */
    public function setReceivedAt(DateTime $receivedAt) : Answer
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedAt() : ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTime $updatedAt
     *
     * @return Answer
     */
    public function setUpdatedAt(DateTime $updatedAt) : Answer
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @param Choice $choice
     *
     * @return bool
     */
    public function hasChoice(Choice $choice) : bool
    {
        return $this->choices->contains($choice);
    }

    /**
     * @return array
     */
    public function getChoiceLabels() : array
    {
        $labels = [];

        foreach ($this->choices as $choice) {
            /* @var Choice $choice */
            $labels[] = $choice->getLabel();
        }

        return $labels;
    }

    /**
     * @return bool|null
     */
    public function isUnclear() : ?bool
    {
        return $this->unclear;
    }

    /**
     * @param bool $unclear
     *
     * @return Answer
     */
    public function setUnclear(bool $unclear) : self
    {
        $this->unclear = $unclear;

        return $this;
    }

    /**
     * @return bool
     */
    public function isValid() : bool
    {
        return $this->choices->count() > 0;
    }

    /**
     * @return Collection|Choice[]
     */
    public function getChoices() : Collection
    {
        return $this->choices;
    }

    /**
     * @param Choice $choice
     *
     * @return Answer
     */
    public function addChoice(Choice $choice) : self
    {
        if (!$this->choices->contains($choice)) {
            $this->choices[] = $choice;
        }

        return $this;
    }

    /**
     * @param Choice $choice
     *
     * @return Answer
     */
    public function removeChoice(Choice $choice) : self
    {
        if ($this->choices->contains($choice)) {
            $this->choices->removeElement($choice);
        }

        return $this;
    }

    /**
     * @return string|null
     */
    public function getByAdmin() : ?string
    {
        return $this->byAdmin;
    }

    /**
     * @param string $byAdmin
     *
     * @return Answer
     */
    public function setByAdmin(string $byAdmin)
    {
        $this->byAdmin = $byAdmin;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        if (!$this->getUpdatedAt()) {
            $this->setUpdatedAt(new DateTime());
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        if (!$this->getUpdatedAt()) {
            $this->setUpdatedAt(new DateTime());
        }
    }

    public function getSentiment() : ?int
    {
        return $this->sentiment;
    }

    public function setSentiment(?int $sentiment) : self
    {
        $this->sentiment = $sentiment;

        return $this;
    }

    public function getFace() : ?string
    {
        if (null === $this->sentiment || strlen($this->raw) < 10) {
            return null;
        }

        switch ($this->sentiment) {
            case $this->sentiment < -75:
                return '😡';
            case $this->sentiment < -50:
                return '😩';
            case $this->sentiment < -25:
                return '☹';
            case $this->sentiment < 25:
                return '😐';
            case $this->sentiment < 50:
                return '🙂';
            case $this->sentiment < 75:
                return '😀';
            default:
                return '🤩';
        }
    }

    public function getMagnitude() : ?int
    {
        return $this->magnitude;
    }

    public function setMagnitude(?int $magnitude) : self
    {
        $this->magnitude = $magnitude;

        return $this;
    }
}

<?php

namespace App\Entity;

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
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $receivedAt;

    /**
     * Date of the answer's last update
     *
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * A Choice if answer is a valid choice from the communication
     *
     * @var Choice|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Choice")
     */
    private $choice;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Answer
     */
    public function setId(int $id): Answer
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Message
     */
    public function getMessage(): Message
    {
        return $this->message;
    }

    /**
     * @param Message $message
     *
     * @return $this
     */
    public function setMessage($message): Answer
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getRaw(): string
    {
        return $this->raw;
    }

    /**
     * @param string $raw
     *
     * @return Answer
     */
    public function setRaw(string $raw): Answer
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReceivedAt(): \DateTime
    {
        return $this->receivedAt;
    }

    /**
     * @param \DateTime $receivedAt
     *
     * @return Answer
     */
    public function setReceivedAt(\DateTime $receivedAt): Answer
    {
        $this->receivedAt = $receivedAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTime $updatedAt
     *
     * @return Answer
     */
    public function setUpdatedAt(\DateTime $updatedAt): Answer
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Choice|null
     */
    public function getChoice(): ?Choice
    {
        return $this->choice;
    }

    /**
     * @param Choice|null $choice
     *
     * @return Answer
     */
    public function setChoice(?Choice $choice): Answer
    {
        $this->choice = $choice;

        return $this;
    }

    /**
     * @param Choice $choice
     *
     * @return bool
     */
    public function isChoice(Choice $choice): bool
    {
        return $this->choice && $choice->getId() === $this->choice->getId();
    }

    /**
     * @ORM\PrePersist
     */
    public function onPrePersist()
    {
        $this->setUpdatedAt(new \DateTime());
    }

    /**
     * @ORM\PreUpdate
     */
    public function onPreUpdate()
    {
        $this->setUpdatedAt(new \DateTime());
    }
}

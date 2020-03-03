<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(indexes={
 *     @ORM\Index(name="message_idx", columns={"message_id"}),
 *     @ORM\Index(name="codex"  , columns={"code"}),
 *     @ORM\Index(name="prefixx"  , columns={"volunteer_id", "prefix"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\MessageRepository")
 */
class Message
{
    const MIN_LENGTH = 5;

    const MAX_LENGTH_SMS   = 300;
    const MAX_LENGTH_EMAIL = 1600;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * This is the message id given by the SMS provider on success.
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    private $messageId;

    /**
     * @var Volunteer
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Volunteer")
     */
    private $volunteer;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $sent;

    /**
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $cost = 0.0;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=3)
     */
    private $currency;

    /**
     * @var Answer[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Answer", mappedBy="message", cascade={"all"})
     * @ORM\OrderBy({"id" = "DESC"})
     */
    private $answers;

    /**
     * @var Communication
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Communication", inversedBy="messages")
     */
    private $communication;

    /**
     * Keep this field binary to preserve case sensitiveness.
     *
     * @var string
     *
     * @ORM\Column(type="binary", length=8, nullable=true)
     */
    private $code;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\GeoLocation", mappedBy="message", cascade={"all"})
     */
    private $geoLocation;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $prefix;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->sent = false;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id): Message
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessageId(): ?string
    {
        return $this->messageId;
    }

    /**
     * @param string $messageId
     *
     * @return $this
     */
    public function setMessageId(string $messageId): Message
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * @return Volunteer
     */
    public function getVolunteer(): Volunteer
    {
        return $this->volunteer;
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return $this
     */
    public function setVolunteer($volunteer): Message
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * @param bool $sent
     *
     * @return $this
     */
    public function setSent($sent): Message
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * @return float
     */
    public function getCost(): float
    {
        return $this->cost;
    }

    /**
     * @param float $cost
     *
     * @return Message
     */
    public function setCost(float $cost): Message
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     *
     * @return Message
     */
    public function setCurrency(string $currency): Message
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return array|Collection
     */
    public function getAnswers()
    {
        return $this->answers;
    }

    /**
     * @param array $answers
     *
     * @return $this
     */
    public function setAnswers($answers): Message
    {
        $this->answers = $answers;

        return $this;
    }

    /**
     * @param Answer $answer
     *
     * @return Message
     */
    public function addAnswser(Answer $answer): Message
    {
        $this->answers[] = $answer;

        return $this;
    }

    /**
     * @param Answer $answer
     */
    public function removeAnswer(Answer $answer): void
    {
        foreach ($this->answers as $key => $object) {
            if ($object->getId() === $answer->getId()) {
                unset($this->answers[$key]);
            }
        }
    }

    /**
     * @return Communication
     */
    public function getCommunication(): Communication
    {
        return $this->communication;
    }

    /**
     * @param Communication $communication
     *
     * @return $this
     */
    public function setCommunication($communication): Message
    {
        $this->communication = $communication;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode(): string
    {
        if (gettype($this->code) === 'resource') {
            return stream_get_contents($this->code);
        }

        return $this->code;
    }

    /**
     * @param string|resource $code
     */
    public function setCode($code): void
    {
        $this->code = $code;
    }

    /**
     * @return GeoLocation|null
     */
    public function getGeoLocation(): ?GeoLocation
    {
        return $this->geoLocation;
    }

    /**
     * @param GeoLocation|null $geoLocation
     *
     * @return Message
     */
    public function setGeoLocation(?GeoLocation $geoLocation): self
    {
        $this->geoLocation = $geoLocation;

        // set (or unset) the owning side of the relation if necessary
        $newMessage = $geoLocation === null ? null : $this;
        if ($newMessage !== $geoLocation->getMessage()) {
            $geoLocation->setMessage($newMessage);
        }

        return $this;
    }

    /**
     * @param Choice $choice
     *
     * @return Answer
     */
    public function getAnswerByChoice(Choice $choice): ?Answer
    {
        foreach ($this->answers ?? [] as $answer) {
            if ($answer->hasChoice($choice)) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * @return Answer|null
     */
    public function getLastAnswer(): ?Answer
    {
        if ($this->answers) {
            $answers = $this->answers->toArray();

            return reset($answers) ?: null;
        }

        return null;
    }

    /**
     * Returns invalid answers only if no valid answer has been ticked.
     *
     * @return null|Answer
     */
    public function getInvalidAnswer(): ?Answer
    {
        if ($this->hasValidAnswer()) {
            return null;
        }

        return $this->getLastAnswer();
    }

    /**
     * @return bool
     */
    public function hasValidAnswer(): bool
    {
        foreach ($this->answers as $answer) {
            if ($answer->isValid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * An answer is unclear if, for the current communication, volunteer
     * answered at least 1 message that is invalid or does not exactly match
     * the expected answers.
     *
     * @return bool
     */
    public function isUnclear(): bool
    {
        if ($this->getInvalidAnswer()) {
            return false;
        }

        foreach ($this->answers ?? [] as $answer) {
            /* @var Answer $answer */
            if ($answer->isUnclear()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Choice[]
     */
    public function getChoices(): array
    {
        $choices = [];

        foreach ($this->answers as $answer) {
            foreach ($answer->getChoices() as $choice) {
                $choices[] = $choice;
            }
        }

        return $choices;
    }

    /**
     * @return string|null
     */
    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     *
     * @return Message
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix = $prefix;

        return $this;
    }
}

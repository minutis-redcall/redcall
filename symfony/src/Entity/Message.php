<?php

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(indexes={
 *     @ORM\Index(name="message_idx", columns={"message_id"}),
 *     @ORM\Index(name="web_codex"  , columns={"web_code"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\MessageRepository")
 */
class Message
{
    const MIN_LENGTH = 5;

    // Don't put an exact modulo of 160 because possible answers
    // are not counted along with the message length.
    const MAX_LENGTH = 300;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * This is the message id given by the SMS provider on success.
     *
     * @ORM\Column(type="string", length=20, nullable=true)
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
     * @var Answer[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Answer", mappedBy="message", cascade={"persist"})
     * @ORM\OrderBy({"receivedAt" = "DESC"})
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
    private $webCode;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\GeoLocation", mappedBy="message", cascade={"persist", "remove"})
     */
    private $geoLocation;

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
    public function getWebCode(): string
    {
        if (gettype($this->webCode) === 'resource') {
            return stream_get_contents($this->webCode);
        }

        return $this->webCode;
    }

    /**
     * @param string|resource $webCode
     */
    public function setWebCode($webCode): void
    {
        $this->webCode = $webCode;
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
     * @param string $code
     *
     * @return bool
     */
    public function alreadyAnsweredChoiceCode(string $code): bool
    {
        foreach ($this->getAnswers() ?? [] as $answer) {
            if ($code == $answer->getChoice()->getCode()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Choice $choice
     *
     * @return Answer
     */
    public function getAnswerByChoice(Choice $choice): ?Answer
    {
        $answer = $this->getLastAnswer();
        if ($answer && $answer->isChoice($choice)) {
            return $answer;
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
        $lastAnswer = $this->getLastAnswer();
        if ($lastAnswer && null === $lastAnswer->getChoice()) {
            return $lastAnswer;
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasValidAnswer(): bool
    {
        return $this->getLastAnswer() && $this->getLastAnswer()->getChoice();
    }
}

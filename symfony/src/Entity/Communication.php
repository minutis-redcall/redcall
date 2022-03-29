<?php

namespace App\Entity;

use App\Task\SendCallTask;
use App\Task\SendEmailTask;
use App\Task\SendSmsTask;
use App\Tools\GSM;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommunicationRepository")
 * @ORM\Table(indexes={
 *     @ORM\Index(name="last_activity_idx", columns={"last_activity_at"})
 * })
 * @ORM\HasLifecycleCallbacks()
 */
class Communication
{
    // TODO use an MyCLabs\Enum
    const TYPE_SMS   = 'sms';
    const TYPE_CALL  = 'call';
    const TYPE_EMAIL = 'email';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Campaign
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Campaign", inversedBy="communications")
     */
    private $campaign;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $label;

    /**
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=80, nullable=true)
     */
    private $subject;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var Message[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="communication", cascade={"persist"})
     * @ORM\OrderBy({"updatedAt" = "DESC"})
     */
    private $messages = [];

    /**
     * @var Choice[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Choice", mappedBy="communication", cascade={"persist"})
     */
    private $choices = [];

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $multipleAnswer = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Volunteer")
     */
    private $volunteer;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    private $shortcut;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $raw;

    /**
     * @var Report|null
     *
     * @ORM\OneToOne(targetEntity=Report::class, inversedBy="communication", cascade={"persist", "remove"})
     */
    private $report;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastActivityAt;

    /**
     * @var Media[]
     *
     * @ORM\OneToMany(targetEntity=Media::class, mappedBy="communication", cascade={"persist", "remove"})
     */
    private $images;

    /**
     * @ORM\Column(type="string", length=5)
     */
    private $language;

    public function __construct()
    {
        $this->images = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return Campaign
     */
    public function getCampaign() : Campaign
    {
        return $this->campaign;
    }

    /**
     * @param Campaign $campaign
     *
     * @return $this
     */
    public function setCampaign(Campaign $campaign) : self
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel() : ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel(?string $label) : self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType(string $type) : self
    {
        $this->type = $type;

        return $this;
    }

    public function isSms() : bool
    {
        return self::TYPE_SMS === $this->type;
    }

    public function isCall() : bool
    {
        return self::TYPE_CALL === $this->type;
    }

    public function isEmail() : bool
    {
        return self::TYPE_EMAIL === $this->type;
    }

    /**
     * @return string|null
     */
    public function getSubject() : ?string
    {
        return $this->subject;
    }

    /**
     * @param string|null $subject
     *
     * @return Communication
     */
    public function setSubject(?string $subject) : Communication
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBody() : ?string
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return $this
     */
    public function setBody(string $body) : self
    {
        $this->body = $body;

        return $this;
    }

    public function getLimitedBody(int $limit = 300) : string
    {
        if (mb_strlen($this->body) > $limit) {
            return sprintf('%s...', mb_substr($this->body, 0, $limit - 3));
        }

        return $this->body;
    }

    public function canExpandBody() : string
    {
        return $this->isEmail() || $this->body !== $this->getLimitedBody();
    }

    /**
     * @return Message[] $messages
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     *
     * @return $this
     */
    public function setMessages(array $messages) : self
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @param Message $message
     *
     * @return $this
     */
    public function addMessage(Message $message) : self
    {
        $this->messages[] = $message;
        $message->setCommunication($this);

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt() : DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt(\DateTime $createdAt) : self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Choice[]|Collection
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @param $choices
     *
     * @return $this
     */
    public function setChoices($choices) : self
    {
        $this->choices = $choices;

        return $this;
    }

    /**
     * @param Choice $choice
     *
     * @return $this
     */
    public function addChoice(Choice $choice)
    {
        $this->choices[] = $choice;
        $choice->setCommunication($this);

        return $this;
    }

    /**
     * @param string|null $prefix
     * @param string      $code
     *
     * @return Choice|null
     */
    public function getChoiceByCode(?string $prefix, string $code) : ?Choice
    {
        // Direct answer (used the word instead of the code)
        foreach ($this->getChoices() as $choice) {
            if (mb_strtolower($code) === mb_strtolower($choice->getLabel())) {
                return $choice;
            }
        }

        if (!$prefix) {
            return null;
        }

        $code = preg_replace('/([a-z]+)\s*(\d)/ui', '$1$2', $code);

        $codes = explode(' ', trim($code));
        foreach ($codes as $code) {
            $matches = [];
            preg_match('/^([a-zA-Z]+)(\d)/', $code, $matches);
            if (3 === count($matches)) {
                // Invalid prefix: do not take any choice
                $codePrefix = strtoupper($matches[1]);
                if ($prefix !== $codePrefix) {
                    continue;
                }

                /**
                 * @var Choice $choice
                 */
                foreach ($this->getChoices() as $choice) {
                    if ($choice->getCode() == $matches[2]) {
                        return $choice;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param string|null $prefix
     * @param string      $raw
     *
     * @return array
     */
    public function getAllChoicesInText(?string $prefix, string $raw) : array
    {
        if (!$prefix) {
            return [];
        }

        $choices = [];

        foreach (array_filter(explode(' ', trim($raw))) as $split) {
            $choices[] = $this->getChoiceByCode($prefix, $split);
        }

        return array_filter($choices);
    }

    public function getFirstChoice() : ?Choice
    {
        $choices = $this->choices;
        if ($choices instanceof Collection) {
            $choices = $choices->toArray();
        }

        if ($choices) {
            return reset($choices);
        }

        return null;
    }

    public function getChoiceByLabel(string $label) : ?Choice
    {
        foreach ($this->choices as $choice) {
            if ($label === $choice->getLabel()) {
                return $choice;
            }
        }

        return null;
    }

    /**
     * @return bool
     */
    public function isMultipleAnswer() : bool
    {
        return $this->multipleAnswer;
    }

    /**
     * @param bool $multipleAnswer
     *
     * @return Communication
     */
    public function setMultipleAnswer(bool $multipleAnswer) : Communication
    {
        $this->multipleAnswer = $multipleAnswer;

        return $this;
    }

    /**
     * Returns true if message body doesn't exactly match expected choices.
     *
     * @param string|null $prefix
     * @param string      $message
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function isUnclear(?string $prefix, string $message) : bool
    {
        if (!$prefix) {
            return false;
        }

        $message = preg_replace('/([a-zA-Z]+)\s*(\d)/', '$1$2', $message);

        $words   = explode(' ', trim($message));
        $choices = [];
        foreach ($words as $index => $word) {
            // No prefix
            preg_match('/^([a-zA-Z]+)(\d)/', $word, $matches);
            if (count($matches) !== 3) {
                return true;
            }

            // Invalid prefix
            $givenPrefix = strtoupper($matches[1]);
            if ($givenPrefix !== $prefix) {
                return true;
            }

            $choice = $this->getChoiceByCode($prefix, $word);

            // Answer contain something else than expected choice codes
            if (!$choice) {
                return true;
            }

            // Answer repeated
            if (in_array($choice, $choices)) {
                return true;
            }

            $choices[] = $choice;
        }

        // Answer does not match any choice
        if (count($choices) == 0) {
            return false;
        }

        // Communication requires 1 answer, but several were given
        if (!$this->multipleAnswer && count($choices) > 1) {
            return false;
        }

        return false;
    }

    public function getVolunteer() : ?Volunteer
    {
        return $this->volunteer;
    }

    public function setVolunteer(?Volunteer $volunteer) : self
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    public function getShortcut() : ?string
    {
        return $this->shortcut;
    }

    public function setShortcut(?string $shortcut) : self
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    /**
     * @param string $body
     *
     * @return float
     */
    public function getEstimatedCost(string $body) : float
    {
        $parts = GSM::getSMSParts($body);

        $estimated = 0;
        switch ($this->getType()) {
            case self::TYPE_SMS:
                $estimated = count($parts) * count($this->getMessages()) * Message::SMS_COST;
                break;
            case self::TYPE_CALL:
                $estimated = count($this->getMessages()) * Message::CALL_COST;
                break;
            case self::TYPE_EMAIL:
                $estimated = count($this->getMessages()) * Message::EMAIL_COST;
                break;
        }

        return $estimated;
    }

    public function getInvalidAnswersCount() : int
    {
        $count = 0;
        foreach ($this->messages as $message) {
            /** @var Message $message */
            if ($message->getInvalidAnswer()) {
                $count++;
            }
        }

        return $count;
    }

    public function noAnswersCount() : int
    {
        $count = 0;
        foreach ($this->messages as $message) {
            /** @var Message $message */
            if (!$message->hasAnswer()) {
                $count++;
            }
        }

        return $count;
    }

    public function getRaw() : ?string
    {
        return $this->raw;
    }

    public function setRaw(?string $raw) : self
    {
        $this->raw = $raw;

        return $this;
    }

    /**
     * A volunteer is reachable if:
     * - he has a phone number (for sms/calls) or an email (for emails)
     * - he is opted-in
     *
     * @return int
     */
    public function countReachables() : int
    {
        $count = 0;

        foreach ($this->getMessages() as $message) {
            switch ($this->type) {
                case self::TYPE_SMS:
                case self::TYPE_CALL:
                    if ($message->getVolunteer()->isPhoneNumberOptin() && $message->getVolunteer()->getPhoneNumber() && !$message->getError()) {
                        $count++;
                    }

                    break;
                case self::TYPE_EMAIL:
                    if ($message->getVolunteer()->isEmailOptin() && $message->getVolunteer()->getEmail() && !$message->getError()) {
                        $count++;
                    }

                    break;
            }
        }

        return $count;
    }

    public function getSendTaskName() : string
    {
        switch ($this->type) {
            case self::TYPE_SMS:
                return SendSmsTask::class;
            case self::TYPE_CALL:
                return SendCallTask::class;
            case self::TYPE_EMAIL:
                return SendEmailTask::class;
        }
    }

    public function getProgression() : array
    {
        $msgsSent = 0;
        $replies  = 0;

        foreach ($this->getMessages() as $message) {
            if ($message->isSent()) {
                $msgsSent++;
            }
            if ($message->getAnswers()->count()) {
                $replies++;
            }
        }

        return [
            'sent'            => $msgsSent,
            'total'           => $count = count($this->getMessages()),
            'reachable'       => $this->countReachables(),
            'percent'         => $count ? round($msgsSent * 100 / $count, 2) : 0,
            'replies'         => $replies,
            'replies-percent' => $msgsSent ? round($replies * 100 / $msgsSent, 2) : 0,
            'type'            => $this->type,
        ];
    }

    public function getReport() : ?Report
    {
        return $this->report;
    }

    public function setReport(?Report $report) : self
    {
        $this->report = $report;

        return $this;
    }

    public function getLastActivityAt() : ?\DateTimeInterface
    {
        return $this->lastActivityAt;
    }

    public function setLastActivityAt(?\DateTimeInterface $lastActivityAt) : self
    {
        $this->lastActivityAt = $lastActivityAt;

        return $this;
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function onChange()
    {
        $this->lastActivityAt = new \DateTime();
    }

    /**
     * @return Collection|Media[]
     */
    public function getImages() : Collection
    {
        return $this->images;
    }

    public function addImage(Media $image) : self
    {
        if (!$this->images->contains($image)) {
            $this->images[] = $image;
            $image->setCommunication($this);
        }

        return $this;
    }

    public function removeImage(Media $image) : self
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getCommunication() === $this) {
                $image->setCommunication(null);
            }
        }

        return $this;
    }

    public function getLanguage() : ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language) : self
    {
        $this->language = $language;

        return $this;
    }

    public function getMessageCount()
    {
        return count($this->messages);
    }

    public function getChoicePercentage(Choice $choice) : int
    {
        return round($choice->getCount() * 100 / $this->getMessageCount());
    }

    public function getInvalidAnswersPercentage() : int
    {
        return round($this->getInvalidAnswersCount() * 100 / $this->getMessageCount());
    }

    public function getNoAnswersPercentage() : int
    {
        return round($this->noAnswersCount() * 100 / $this->getMessageCount());
    }

    public function getLastAnswerTime(Choice $choice = null) : string
    {
        $lastAnswer = null;
        foreach ($this->messages as $message) {
            if (!$message->getLastAnswer()) {
                continue;
            }

            if ($choice && !$message->getLastAnswer()->getChoices()->contains($choice)) {
                continue;
            }

            if (!$lastAnswer) {
                $lastAnswer = $message->getLastAnswer()->getReceivedAt();
                continue;
            }

            if ($message->getLastAnswer()->getReceivedAt()->getTimestamp() > $lastAnswer->getTimestamp()) {
                $lastAnswer = $message->getLastAnswer();
            }
        }

        return $lastAnswer ? $lastAnswer->format('d/m H:i') : '--:--';
    }
}

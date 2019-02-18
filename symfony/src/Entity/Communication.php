<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CommunicationRepository")
 */
class Communication
{
    const TYPE_SMS   = 'sms';
    const TYPE_EMAIL = 'email';

    const STATUS_PENDING     = 'pending';     // The communication is waiting to be dispatched to volunteers.
    const STATUS_DISPATCHING = 'dispatching'; // The communication is being dispatched.
    const STATUS_DISPATCHED  = 'dispatched';  // The communication was successfully dispatched.
    const STATUS_FAILED      = 'failed';      // The communication failed to be dispatched.

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
     * @var string
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     */
    private $status;

    /**
     * @var string
     *
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
     * Contains volunteer's message statuses, the message itself is inside body.
     *
     * @var array
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Message", mappedBy="communication", cascade={"persist"})
     */
    private $messages;

    /**
     * @var Choice[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Choice", mappedBy="communication", cascade={"persist"})
     */
    private $choices;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $geoLocation = false;

    /**
     * @var boolean
     *
     * @ORM\Column(type="boolean")
     */
    private $multipleAnswer = false;

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
    public function getCampaign(): Campaign
    {
        return $this->campaign;
    }

    /**
     * @param Campaign $campaign
     *
     * @return $this
     */
    public function setCampaign($campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return $this
     */
    public function setType($type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @param string $body
     *
     * @return $this
     */
    public function setBody($body): self
    {
        $this->body = $body;

        return $this;
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
    public function setMessages($messages): self
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @param Message $message
     *
     * @return $this
     */
    public function addMessage(Message $message): self
    {
        $this->messages[] = $message;
        $message->setCommunication($this);

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return Choice[]
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
    public function setChoices($choices): self
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
     * @param int $code
     *
     * @return Choice
     * @throws \Exception
     */
    public function getChoiceByCode(int $code): ?Choice
    {
        /**
         * @var Choice $choice
         */
        foreach ($this->getChoices() as $choice) {
            if ($choice->getCode() == $code) {
                return $choice;
            }
        }

        return null;
    }

    /**
     * @param mixed $raw
     *
     * @return Choice|null
     * @throws \Exception
     */
    public function getChoiceByLabelOrCode($raw): ?Choice
    {
        $raw = mb_strtolower(trim($raw));

        /**
         * @var Choice $choice
         */
        foreach ($this->getChoices() as $choice) {
            // By label
            if ($raw == mb_strtolower($choice->getLabel())) {
                return $choice;
            }

            // By code
            if ($raw == $choice->getCode()) {
                return $choice;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getChoicesAsString()
    {
        $answers = [];
        foreach ($this->choices as $key => $choice) {
            $answers[] = $choice->getLabel();
        }

        return $answers;
    }

    /**
     * @return bool
     */
    public function hasGeoLocation(): bool
    {
        return $this->geoLocation;
    }

    /**
     * @param bool $geoLocation
     *
     * @return Communication
     */
    public function setGeoLocation(bool $geoLocation): Communication
    {
        $this->geoLocation = $geoLocation;

        return $this;
    }

    /**
     * @return bool
     */
    public function isMultipleAnswer(): bool
    {
        return $this->multipleAnswer;
    }

    /**
     * @param bool $multipleAnswer
     *
     * @return Communication
     */
    public function setMultipleAnswer(bool $multipleAnswer): Communication
    {
        $this->multipleAnswer = $multipleAnswer;

        return $this;
    }
}

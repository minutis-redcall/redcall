<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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
    const MIN_LENGTH = 1;

    // TODO move to Enum\Type
    const MAX_LENGTH_SMS   = 300;
    const MAX_LENGTH_CALL  = 1000;
    const MAX_LENGTH_EMAIL = 50000;

    // TODO move to Enum\Type
    const SMS_COST   = 0.05052;
    const CALL_COST  = 0.033;
    const EMAIL_COST = 0.000375;

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
     * @ORM\ManyToOne(targetEntity="App\Entity\Volunteer", inversedBy="messages")
     */
    private $volunteer;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $sent;

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
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $prefix;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Cost", mappedBy="message")
     */
    private $costs;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $error;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $resourceExternalId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->sent      = false;
        $this->answers   = new ArrayCollection();
        $this->costs     = new ArrayCollection();
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return int
     */
    public function getId() : int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return $this
     */
    public function setId($id) : Message
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessageId() : ?string
    {
        return $this->messageId;
    }

    /**
     * @param string $messageId
     *
     * @return $this
     */
    public function setMessageId(string $messageId) : Message
    {
        $this->messageId = $messageId;

        return $this;
    }

    /**
     * @return Volunteer
     */
    public function getVolunteer() : Volunteer
    {
        return $this->volunteer;
    }

    /**
     * @param Volunteer $volunteer
     *
     * @return $this
     */
    public function setVolunteer(Volunteer $volunteer) : Message
    {
        $this->volunteer = $volunteer;

        return $this;
    }

    /**
     * @return bool
     */
    public function isSent() : bool
    {
        return $this->sent;
    }

    /**
     * @param bool $sent
     *
     * @return $this
     */
    public function setSent(bool $sent) : Message
    {
        $this->sent = $sent;

        return $this;
    }

    /**
     * @return bool
     */
    public function canBeSent() : bool
    {
        return !$this->sent && $this->isReachable();
    }

    /**
     * Return approximate cost for a message
     *
     * For billing purposes, this method should not be used because it does
     * not take currencies into account, and calculations using floats are
     * discouraged.
     *
     * @return float
     */
    public function getCost() : float
    {
        $price = 0.0;
        foreach ($this->costs as $cost) {
            /** @var Cost $cost */
            $price += $cost->getPrice();
        }

        return $price;
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
    public function setAnswers(array $answers) : Message
    {
        $this->answers = $answers;

        return $this;
    }

    /**
     * @param Answer $answer
     *
     * @return Message
     */
    public function addAnswser(Answer $answer) : Message
    {
        $this->answers[] = $answer;

        return $this;
    }

    /**
     * @param Answer $answer
     */
    public function removeAnswer(Answer $answer) : void
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
    public function getCommunication() : Communication
    {
        return $this->communication;
    }

    /**
     * @param Communication $communication
     *
     * @return $this
     */
    public function setCommunication(Communication $communication) : Message
    {
        $this->communication = $communication;

        return $this;
    }

    /**
     * @return string
     */
    public function getCode() : string
    {
        if (gettype($this->code) === 'resource') {
            $this->code = stream_get_contents($this->code);
        }

        return $this->code;
    }

    /**
     * @param string|resource $code
     */
    public function setCode($code) : void
    {
        $this->code = $code;
    }

    /**
     * @param Choice $choice
     *
     * @return Answer
     */
    public function getAnswerByChoice(Choice $choice) : ?Answer
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
    public function getLastAnswer(bool $includingAdmins = false) : ?Answer
    {
        foreach ($this->answers as $answer) {
            if (!$includingAdmins && $answer->getByAdmin()) {
                continue;
            }

            return $answer;
        }

        return null;
    }

    /**
     * Returns invalid answers only if no valid answer has been ticked.
     *
     * @return null|Answer
     */
    public function getInvalidAnswer() : ?Answer
    {
        if ($this->hasValidAnswer()) {
            return null;
        }

        return $this->getLastAnswer();
    }

    public function hasAnswer() : bool
    {
        return null !== $this->getLastAnswer();
    }

    /**
     * @return bool
     */
    public function hasValidAnswer() : bool
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
    public function isUnclear() : bool
    {
        if ($this->getInvalidAnswer()) {
            return false;
        }

        foreach ($this->answers ?? [] as $answer) {
            if ($answer->getByAdmin()) {
                continue;
            }

            /* @var Answer $answer */
            if ($answer->isUnclear()) {
                return true;
            }
        }

        return false;
    }

    public function getUnclear() : ?Answer
    {
        if ($this->getInvalidAnswer()) {
            return null;
        }

        foreach ($this->answers ?? [] as $answer) {
            /* @var Answer $answer */
            if ($answer->isUnclear() && !$answer->getByAdmin()) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * @return Choice[]
     */
    public function getChoices() : array
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
    public function getPrefix() : ?string
    {
        return $this->prefix;
    }

    /**
     * @param string $prefix
     *
     * @return Message
     */
    public function setPrefix(string $prefix) : self
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return Collection|Cost[]
     */
    public function getCosts() : Collection
    {
        return $this->costs;
    }

    public function addCost(Cost $cost) : self
    {
        if (!$this->costs->contains($cost)) {
            $this->costs[] = $cost;
            $cost->setMessage($this);
        }

        return $this;
    }

    public function removeCost(Cost $cost) : self
    {
        if ($this->costs->contains($cost)) {
            $this->costs->removeElement($cost);
            // set the owning side to null (unless already changed)
            if ($cost->getMessage() === $this) {
                $cost->setMessage(null);
            }
        }

        return $this;
    }

    public function getError() : ?string
    {
        return $this->error;
    }

    public function setError(?string $error) : self
    {
        $this->error = $error;

        return $this;
    }

    public function getResourceExternalId() : ?int
    {
        return $this->resourceExternalId;
    }

    public function setResourceExternalId(?int $resourceExternalId) : self
    {
        $this->resourceExternalId = $resourceExternalId;

        return $this;
    }

    public function getUpdatedAt() : \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt) : self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * This signature is used to replace CSRF tokens on answer links sent by email.
     *
     * @return string
     */
    public function getSignature() : string
    {
        return sha1(sprintf('%s%s', $this->getCode(), getenv('APP_SECRET')));
    }

    public function isReachable() : bool
    {
        if ($this->error) {
            return false;
        }

        switch ($this->communication->getType()) {
            case Communication::TYPE_SMS:
                return $this->volunteer->getPhoneNumber()
                       && $this->volunteer->isPhoneNumberOptin()
                       && $this->volunteer->getPhone()->isMobile();
            case Communication::TYPE_CALL:
                return $this->volunteer->getPhoneNumber()
                       && $this->volunteer->isPhoneNumberOptin();
            case Communication::TYPE_EMAIL:
                return $this->volunteer->getEmail()
                       && $this->volunteer->isEmailOptin();
            default:
                return false;
        }
    }

    public function shouldAddMinutisResource() : bool
    {
        $communication = $this->getCommunication();

        $isAddResourceNeeded = false;
        foreach ($communication->getChoices() as $choice) {
            if ($this->getAnswerByChoice($choice) && $communication->getCampaign()->isChoiceShouldCreateResource($choice)) {
                $isAddResourceNeeded = true;
            }
        }

        return $isAddResourceNeeded && !$this->resourceExternalId;
    }

    public function shouldRemoveMinutisResource() : bool
    {
        $communication = $this->getCommunication();

        $isRemoveResourceNeeded = true;
        foreach ($communication->getChoices() as $choice) {
            if ($this->getAnswerByChoice($choice) && $communication->getCampaign()->isChoiceShouldCreateResource($choice)) {
                $isRemoveResourceNeeded = false;
            }
        }

        return $isRemoveResourceNeeded && $this->resourceExternalId;
    }
}

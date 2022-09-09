<?php

namespace App\Entity;

use App\Provider\Minutis\MinutisProvider;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CampaignRepository")
 * @ORM\Table(
 *     indexes={
 *         @ORM\Index(name="platformx", columns={"platform"}),
 *         @ORM\Index(name="expires_atx", columns={"expires_at"})
 *     },
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="codex", columns={"code"})
 *     }
 * )
 */
class Campaign
{
    const DEFAULT_EXPIRATION = 7 * 86400;

    // TODO use a MyCLabs\Enum (Color)
    const TYPE_GREEN        = '1_green';
    const TYPE_LIGHT_ORANGE = '2_light_orange';
    const TYPE_DARK_ORANGE  = '3_dark_orange';
    const TYPE_RED          = '4_red';

    const TYPES = [
        self::TYPE_GREEN,
        self::TYPE_LIGHT_ORANGE,
        self::TYPE_DARK_ORANGE,
        self::TYPE_RED,
    ];

    const COLORS      = [
        self::TYPE_GREEN        => '#009933',
        self::TYPE_LIGHT_ORANGE => '#ff9900',
        self::TYPE_DARK_ORANGE  => '#ff6600',
        self::TYPE_RED          => '#ff3300',
    ];
    const BACKGROUNDS = [
        self::TYPE_GREEN        => '#d7f5e1',
        self::TYPE_LIGHT_ORANGE => '#faf0e1',
        self::TYPE_DARK_ORANGE  => '#faebe1',
        self::TYPE_RED          => '#fce4de',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=5)
     */
    private $platform;

    /**
     * @var string
     *
     * @ORM\Column(type="binary", length=8, nullable=true)
     */
    private $code;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $label;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=80)
     */
    // TODO rename to color
    private $type = self::TYPE_GREEN;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(type="boolean")
     */
    private $active = true;

    /**
     * @var Communication[]
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Communication", mappedBy="campaign", cascade={"persist"})
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private $communications;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $notesUpdatedAt;

    /**
     * @ORM\ManyToOne(targetEntity=Volunteer::class)
     */
    private $volunteer;

    /**
     * @ORM\Column(type="datetime")
     */
    private $expiresAt;

    /**
     * @ORM\OneToOne(targetEntity=Operation::class, inversedBy="campaign")
     */
    private $operation;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    public function getPlatform() : string
    {
        return $this->platform;
    }

    public function setPlatform(string $platform) : Campaign
    {
        $this->platform = $platform;

        return $this;
    }

    public function getCode() : ?string
    {
        if (gettype($this->code) === 'resource') {
            $this->code = stream_get_contents($this->code);
        }

        return $this->code;
    }

    public function setCode(?string $code) : Campaign
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel() : string
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    public function getCreatedAt() : DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isActive() : int
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Communication[]
     */
    public function getCommunications()
    {
        return $this->communications;
    }

    public function setCommunications($communications)
    {
        $this->communications = $communications;

        return $this;
    }

    public function getCommunicationByType(string $type) : ?Communication
    {
        /** @var Communication $communication */
        foreach ($this->communications as $communication) {
            if ($communication->getType() === $type) {
                return $communication;
            }
        }

        return null;
    }

    public function addCommunication(Communication $communication)
    {
        // Campaign expires after minimum 7 days when creating a new communication
        if ($this->expiresAt->getTimestamp() < time() + self::DEFAULT_EXPIRATION) {
            $this->expiresAt = (new \DateTime())->setTimestamp(time() + self::DEFAULT_EXPIRATION);
        }

        $this->communications[] = $communication;
        $communication->setCampaign($this);

        return $this;
    }

    public function getCampaignStatus(TranslatorInterface $translator) : array
    {
        $data = [
            'notes'          => [
                'content'                 => nl2br($this->notes),
                'notes-updated-timestamp' => $this->notesUpdatedAt ? $this->notesUpdatedAt->getTimestamp() : 0,
                'notes-updated-date'      => $this->notesUpdatedAt ? $this->notesUpdatedAt->format('d/m/Y') : null,
                'notes-updated-time'      => $this->notesUpdatedAt ? $this->notesUpdatedAt->format('H:i') : null,
            ],
            'communications' => [],
        ];
        foreach ($this->getCommunications() as $communication) {
            $msgsSent = 0;

            // Messages & Answers
            foreach ($communication->getMessages() as $message) {
                if ($message->getMessageId()) {
                    $msgsSent++;
                }

                $choices = [];
                foreach ($communication->getChoices() as $choice) {
                    $choices[$choice->getId()] = null;
                    $answer                    = $message->getAnswerByChoice($choice);
                    $choices[$choice->getId()] = null;
                    if ($answer) {
                        $choices[$choice->getId()] = $answer->getReceivedAt()->format('H:i');
                    }
                }

                /** @var Answer $unclearAnswer */
                $unclearAnswer = $message->getUnclear();
                /** @var Answer $invalidAnswer */
                $invalidAnswer = $message->getInvalidAnswer();

                $data['communications'][$communication->getId()]['type']                   = $communication->getType();
                $data['communications'][$communication->getId()]['msg'][$message->getId()] = [
                    'sent'               => $message->isSent(),
                    'error'              => $translator->trans($message->getError()),
                    'has-answer'         => $message->getAnswers()->count(),
                    'choices'            => $choices,
                    'has-invalid-answer' => [
                        'raw'  => $invalidAnswer ? $invalidAnswer->getSafeRaw() : null,
                        'time' => $invalidAnswer ? $invalidAnswer->getReceivedAt()->format('H:i') : null,
                    ],
                    'has-unclear-answer' => [
                        'raw'  => $unclearAnswer ? $unclearAnswer->getSafeRaw() : null,
                        'time' => $unclearAnswer ? $unclearAnswer->getReceivedAt()->format('H:i') : null,
                    ],
                ];
            }

            // Progression
            $data['communications'][$communication->getId()]['progress'] = $communication->getProgression();

            // Choice counts
            foreach ($communication->getChoices() as $choice) {
                $data['communications'][$communication->getId()]['choices'][$choice->getId()] = $choice->getCount();
            }
        }

        return $data;
    }

    public function getCampaignProgression() : array
    {
        $data = [];
        foreach ($this->getCommunications() as $communication) {
            $data[$communication->getId()] = $communication->getProgression();
        }

        return $data;
    }

    public function getCost() : float
    {
        $cost = 0.0;

        foreach ($this->getCommunications() as $communication) {
            $cost += $communication->getCost();
        }

        return $cost;
    }

    public function getNotes() : ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes) : self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getNotesUpdatedAt() : ?\DateTimeInterface
    {
        return $this->notesUpdatedAt;
    }

    public function setNotesUpdatedAt(?\DateTimeInterface $notesUpdatedAt) : self
    {
        $this->notesUpdatedAt = $notesUpdatedAt;

        return $this;
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

    public function getExpiresAt() : ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt) : self
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isReportReady() : bool
    {
        foreach ($this->communications as $communication) {
            /** @var Communication $communication */
            if (!$communication->getReport()) {
                return false;
            }
        }

        return true;
    }

    public function equals(Campaign $campaign)
    {
        return $this->id === $campaign->getId();
    }

    public function hasOperation() : bool
    {
        return null !== $this->operation;
    }

    public function getOperation() : ?Operation
    {
        return $this->operation;
    }

    public function setOperation(?Operation $operation) : self
    {
        $this->operation = $operation;

        return $this;
    }

    public function getOperationUrl(MinutisProvider $minutis) : ?string
    {
        return $this->getOperation() ? $minutis->getOperationUrl($this->getOperation()->getOperationExternalId()) : null;
    }

    public function isChoiceShouldCreateResource(Choice $choice) : bool
    {
        return $this->operation && $this->operation->hasChoice($choice);
    }

    public function hasChoices()
    {
        foreach ($this->communications as $communication) {
            /** @var Communication $communication */
            if ($communication->getChoices()->count()) {
                return true;
            }
        }

        return false;
    }

    public function getRenderedShortcuts() : string
    {
        $shortcuts = [];
        foreach ($this->communications as $communication) {
            $shortcuts[] = $communication->getShortcut();
        }
        $shortcuts = array_filter($shortcuts);

        if (!$shortcuts) {
            return '';
        }

        $shortcuts = array_unique($shortcuts);

        return sprintf('(%s)', implode(', ', $shortcuts));
    }
}

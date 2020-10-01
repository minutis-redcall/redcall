<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CampaignRepository")
 */
class Campaign
{
    /**
     * Campaign types
     */
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

    const COLORS = [
        self::TYPE_GREEN        => '#009933',
        self::TYPE_LIGHT_ORANGE => '#ff9900',
        self::TYPE_DARK_ORANGE  => '#ff6600',
        self::TYPE_RED          => '#ff3300',
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
     * @ORM\OneToMany(targetEntity="App\Entity\Communication", mappedBy="campaign", cascade={"persist"})
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private $communications;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Structure", inversedBy="campaigns")
     * @ORM\OrderBy({"identifier" = "ASC"})
     */
    private $structures;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $notes;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $notesUpdatedAt;

    public function __construct()
    {
        $this->structures = new ArrayCollection();
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
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     *
     * @return $this
     */
    public function setLabel($label)
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
     * @throws LogicException
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     *
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return int
     */
    public function isActive(): int
    {
        return $this->active;
    }

    /**
     * @param int $active
     *
     * @return $this
     */
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

    /**
     * @param mixed $communications
     *
     * @return $this
     */
    public function setCommunications($communications)
    {
        $this->communications = $communications;

        return $this;
    }

    /**
     * @param string $type
     *
     * @return Communication|null
     */
    public function getCommunicationByType(string $type): ?Communication
    {
        /** @var Communication $communication */
        foreach ($this->communications as $communication) {
            if ($communication->getType() === $type) {
                return $communication;
            }
        }

        return null;
    }

    /**
     * @param Communication $communication
     *
     * @return $this
     */
    public function addCommunication(Communication $communication)
    {
        $this->communications[] = $communication;
        $communication->setCampaign($this);

        return $this;
    }

    /**
     * @return array
     */
    public function getCampaignStatus(): array
    {
        $data = [
            'notes' => [
                'content' => nl2br($this->notes),
                'notes-updated-timestamp' => $this->notesUpdatedAt ? $this->notesUpdatedAt->getTimestamp() : 0,
                'notes-updated-date' => $this->notesUpdatedAt ? $this->notesUpdatedAt->format('d/m/Y') : null,
                'notes-updated-time' => $this->notesUpdatedAt ? $this->notesUpdatedAt->format('H:i') : null,
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

                $unclearAnswer = $message->getUnclear();
                $invalidAnswer = $message->getInvalidAnswer();

                $data['communications'][$communication->getId()]['msg'][$message->getId()] = [
                    'sent'               => $message->isSent(),
                    'error'              => $message->getError(),
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

            // Geolocation
            $data['communications'][$communication->getId()]['geo'] = [];
            if ($communication->hasGeoLocation()) {
                foreach ($communication->getMessages() as $message) {
                    if ($message->getGeoLocation()) {
                        $data['communications'][$communication->getId()]['geo'][$message->getId()] = [
                            'display-name' => $message->getVolunteer()->getDisplayName(),
                            'phone-number' => $message->getVolunteer()->getPhoneNumber(),
                            'longitude'    => $message->getGeoLocation()->getLongitude(),
                            'latitude'     => $message->getGeoLocation()->getLatitude(),
                            'accuracy'     => $message->getGeoLocation()->getAccuracy(),
                            'heading'      => $message->getGeoLocation()->getHeading(),
                        ];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getCampaignProgression(): array
    {
        $data = [];
        foreach ($this->getCommunications() as $communication) {
            $data[$communication->getId()] = $communication->getProgression();
        }

        return $data;
    }

    /**
     * @return float
     */
    public function getCost(): float
    {
        $cost = 0.0;

        foreach ($this->getCommunications() as $communication) {
            $cost += $communication->getCost();
        }

        return $cost;
    }

    /**
     * @return Collection|Structure[]
     */
    public function getStructures(): Collection
    {
        return $this->structures;
    }

    public function addStructure(Structure $structure): self
    {
        if (!$this->structures->contains($structure)) {
            $this->structures[] = $structure;
        }

        return $this;
    }

    public function removeStructure(Structure $structure): self
    {
        if ($this->structures->contains($structure)) {
            $this->structures->removeElement($structure);
        }

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function getNotesUpdatedAt(): ?\DateTimeInterface
    {
        return $this->notesUpdatedAt;
    }

    public function setNotesUpdatedAt(?\DateTimeInterface $notesUpdatedAt): self
    {
        $this->notesUpdatedAt = $notesUpdatedAt;

        return $this;
    }
}

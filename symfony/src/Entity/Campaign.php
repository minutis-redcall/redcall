<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CampaignRepository")
 */
class Campaign
{
    /**
     * Campaign types
     */
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
    private $type = self::TYPE_GREEN;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", options={"default" : 1})
     */
    private $active;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Communication", mappedBy="campaign", cascade={"persist"})
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private $communications;

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
     * @throws \LogicException
     */
    public function setType(string $type)
    {
        $this->type = $type;

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
        $data = [];
        foreach ($this->getCommunications() as $communication) {
            $msgsSent = 0;
            foreach ($communication->getMessages() as $message) {
                if ($message->getMessageId()) {
                    $msgsSent++;
                }

                $choices = [];
                foreach ($communication->getChoices() as $choice) {
                    $answer =  $message->getAnswerByChoice($choice);
                    $choices[$choice->getId()] = null;
                    if ($answer) {
                        $choices[$choice->getId()] = $answer->getReceivedAt()->format('H:i');
                    }
                }

                $invalidAnswer = $message->getInvalidAnswer();

                $data[$communication->getId()]['msg'][$message->getId()] = [
                    'sent'               => $message->isSent(),
                    'choices'            => $choices,
                    'has-invalid-answer' => [
                        'raw' => $invalidAnswer ? $invalidAnswer->getSafeRaw() : null,
                        'time' => $invalidAnswer ? $invalidAnswer->getReceivedAt()->format('H:i') : null,
                    ],
                ];
            }

            $data[$communication->getId()]['progress'] = [
                'sent'    => $msgsSent,
                'total'   => $count = count($communication->getMessages()),
                'percent' => round($msgsSent * 100 / $count, 2),
            ];
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
            $msgsSent = 0;
            foreach ($communication->getMessages() as $message) {
                if ($message->getMessageId()) {
                    $msgsSent++;
                }
            }

            $data[$communication->getId()] = [
                'sent'    => $msgsSent,
                'total'   => $count = count($communication->getMessages()),
                'percent' => round($msgsSent * 100 / $count, 2),
            ];
        }

        return $data;
    }
}

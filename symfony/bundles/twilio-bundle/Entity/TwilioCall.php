<?php

namespace Bundles\TwilioBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uuid_idx", columns={"uuid"})
 *     },
 *     indexes={
 *         @ORM\Index(name="sid_idx", columns={"sid"}),
 *         @ORM\Index(name="price_idx", columns={"price"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="Bundles\TwilioBundle\Repository\TwilioCallRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TwilioCall extends BaseTwilio
{
    private const TYPE = 'call';

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $endedAt;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $duration;

    public function getStartedAt() : ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTime $startedAt) : self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt() : ?\DateTime
    {
        return $this->endedAt;
    }

    public function setEndedAt(\DateTime $endedAt) : self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getDuration() : ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration) : self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getType() : string
    {
        return self::TYPE;
    }
}

<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GeoLocationRepository")
 */
class GeoLocation
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=32)
     *
     * @Assert\Range(min=-180, max=180)
     */
    private $longitude;

    /**
     * @ORM\Column(type="string", length=32)
     *
     * @Assert\Range(min=-90, max=90)
     */
    private $latitude;

    /**
     * @ORM\Column(type="integer")
     *
     * @Assert\GreaterThan(0)
     */
    private $accuracy;

    /**
     * @ORM\Column(type="integer", nullable=true)
     *
     * @Assert\Range(min=-1, max=360)
     */
    private $heading;

    /**
     * @ORM\Column(type="datetime")
     */
    private $datetime;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Message", inversedBy="geoLocation", cascade={"persist", "remove"})
     */
    private $message;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     *
     * @return GeoLocation
     */
    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     *
     * @return GeoLocation
     */
    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getAccuracy(): ?int
    {
        return $this->accuracy;
    }

    /**
     * @param int $accuracy
     *
     * @return GeoLocation
     */
    public function setAccuracy(int $accuracy): self
    {
        $this->accuracy = $accuracy;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDatetime(): ?DateTime
    {
        return $this->datetime;
    }

    /**
     * @param DateTime|null $datetime
     *
     * @return $this
     */
    public function setDatetime(?DateTime $datetime)
    {
        $this->datetime = $datetime;

        return $this;
    }

    /**
     * @return Message|null
     */
    public function getMessage(): ?Message
    {
        return $this->message;
    }

    /**
     * @param Message|null $message
     *
     * @return GeoLocation
     */
    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getHeading(): ?int
    {
        return $this->heading;
    }

    /**
     * @param int|null $heading
     *
     * @return $this
     */
    public function setHeading(?int $heading)
    {
        $this->heading = $heading;

        return $this;
    }

    /**
     * @param GeoLocation $geoLocation
     *
     * @return string
     */
    public function getDistance(GeoLocation $geoLocation): string
    {
        $latFrom = deg2rad($this->getLatitude());
        $lonFrom = deg2rad($this->getLongitude());
        $latTo   = deg2rad($geoLocation->getLatitude());
        $lonTo   = deg2rad($geoLocation->getLongitude());

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
                               cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return $this->getReadableDistance($angle * 6370981.162);
    }

    /**
     * @return string
     */
    public function getReadableAccuracy(): string
    {
        return $this->getReadableDistance($this->accuracy);
    }

    /**
     * @param float $distance
     *
     * @return string
     */
    private function getReadableDistance(float $distance): string
    {
        if ($distance >= 1000) {
            return sprintf('%s km', round($distance / 1000, 1));
        }

        return sprintf('%s m', intval($distance));
    }
}

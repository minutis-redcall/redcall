<?php

namespace App\Entity;

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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLongitude(): ?string
    {
        return $this->longitude;
    }

    public function setLongitude(string $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    public function getLatitude(): ?string
    {
        return $this->latitude;
    }

    public function setLatitude(string $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getAccuracy(): ?int
    {
        return $this->accuracy;
    }

    public function setAccuracy(int $accuracy): self
    {
        $this->accuracy = $accuracy;

        return $this;
    }

    public function getDatetime(): ?\DateTime
    {
        return $this->datetime;
    }

    public function setDatetime(?\DateTime $datetime)
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getHeading(): ?int
    {
        return $this->heading;
    }

    public function setHeading(?int $heading)
    {
        $this->heading = $heading;

        return $this;
    }

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

    public function getReadableAccuracy(): string
    {
        return $this->getReadableDistance($this->accuracy);
    }

    private function getReadableDistance(float $distance): string
    {
        if ($distance >= 1000) {
            return sprintf('%s km', round($distance / 1000, 1));
        }

        return sprintf('%s m', intval($distance));
    }
}

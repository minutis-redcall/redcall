<?php

namespace App\Manager;

use App\Entity\Communication;
use App\Entity\GeoLocation;
use App\Repository\GeoLocationRepository;

class GeoLocationManager
{
    /**
     * @var GeoLocationRepository
     */
    private $geoLocationRepository;

    public function __construct(GeoLocationRepository $geoLocationRepository)
    {
        $this->geoLocationRepository = $geoLocationRepository;
    }

    public function getLastGeoLocationUpdateTimestamp(Communication $communication): ?int
    {
        return $this->geoLocationRepository->getLastGeoLocationUpdateTimestamp($communication);
    }

    public function save(GeoLocation $geoLocation)
    {
        return $this->geoLocationRepository->save($geoLocation);
    }
}
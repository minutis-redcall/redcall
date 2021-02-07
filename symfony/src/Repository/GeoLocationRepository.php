<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Communication;
use App\Entity\GeoLocation;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method GeoLocation|null find($id, $lockMode = null, $lockVersion = null)
 * @method GeoLocation|null findOneBy(array $criteria, array $orderBy = null)
 * @method GeoLocation[]    findAll()
 * @method GeoLocation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeoLocationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GeoLocation::class);
    }

    public function getLastGeoLocationUpdateTimestamp(Communication $communication) : ?int
    {
        /* @var GeoLocation $lastGeolocation */
        $lastGeolocation = $this->createQueryBuilder('g')
                                ->join('g.message', 'm')
                                ->join('m.communication', 'c')
                                ->where('c.id = :communicationId')
                                ->setParameter('communicationId', $communication->getId())
                                ->orderBy('g.datetime', 'DESC')
                                ->setMaxResults(1)
                                ->getQuery()
                                ->disableResultCache()
                                ->getOneOrNullResult();

        if ($lastGeolocation) {
            $this->_em->clear();

            return $lastGeolocation->getDatetime()->getTimestamp();
        }

        return null;
    }
}

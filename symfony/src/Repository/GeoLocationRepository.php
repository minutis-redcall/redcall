<?php

namespace App\Repository;

use App\Entity\Communication;
use App\Entity\GeoLocation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method GeoLocation|null find($id, $lockMode = null, $lockVersion = null)
 * @method GeoLocation|null findOneBy(array $criteria, array $orderBy = null)
 * @method GeoLocation[]    findAll()
 * @method GeoLocation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GeoLocationRepository extends ServiceEntityRepository
{
    /**
     * GeoLocationRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, GeoLocation::class);
    }

    /**
     * @param Communication $communication
     *
     * @return int|null
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getLastGeoLocationUpdateTimestamp(Communication $communication): ?int
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
                                ->useResultCache(false)
                                ->getOneOrNullResult();

        if ($lastGeolocation) {
            $this->_em->detach($lastGeolocation);

            return $lastGeolocation->getDatetime()->getTimestamp();
        }

        return null;
    }
}

<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\VolunteerSession;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VolunteerSession|null find($id, $lockMode = null, $lockVersion = null)
 * @method VolunteerSession|null findOneBy(array $criteria, array $orderBy = null)
 * @method VolunteerSession[]    findAll()
 * @method VolunteerSession[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VolunteerSessionRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VolunteerSession::class);
    }

    // /**
    //  * @return VolunteerSession[] Returns an array of VolunteerSession objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?VolunteerSession
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

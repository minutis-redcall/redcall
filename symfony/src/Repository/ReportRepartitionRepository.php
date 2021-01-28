<?php

namespace App\Repository;

use App\Entity\ReportRepartition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ReportRepartition|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReportRepartition|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReportRepartition[]    findAll()
 * @method ReportRepartition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportRepartitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReportRepartition::class);
    }

    // /**
    //  * @return ReportCostRepartition[] Returns an array of ReportCostRepartition objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ReportCostRepartition
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

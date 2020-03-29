<?php

namespace Bundles\TwilioBundle\Repository;

use Bundles\TwilioBundle\Entity\TwilioStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TwilioStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method TwilioStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method TwilioStatus[]    findAll()
 * @method TwilioStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TwilioStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwilioStatus::class);
    }

    public function save(TwilioStatus $entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush();
    }

    // /**
    //  * @return TwilioStatus[] Returns an array of TwilioStatus objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TwilioStatus
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

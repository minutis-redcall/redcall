<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Pegass;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Pegass|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pegass|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pegass[]    findAll()
 * @method Pegass[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PegassRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pegass::class);
    }

    // /**
    //  * @return Pegass[] Returns an array of Pegass objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Pegass
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

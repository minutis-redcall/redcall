<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Cost;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Cost|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cost|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cost[]    findAll()
 * @method Cost[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CostRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cost::class);
    }

    // /**
    //  * @return Cost[] Returns an array of Cost objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Cost
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * Return the costs grouped by c.direction
     *
     * @param \DateTime $from
     * @param \DateTime $to
     * @return QueryBuilder
     * @throws \Exception
     */
    public function getSumOfCost(\DateTime $from, \DateTime $to)
    {
        $sql = 'select sum(price) sum_cost, direction, currency
                from cost
                where created_at > :fromDate
                and created_at <= :toDate
                group by direction';

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('sum_cost', 'cost', 'float')
            ->addScalarResult('direction', 'direction')
            ->addScalarResult('currency', 'currency');

        return $this->_em->createNativeQuery($sql, $rsm)
            ->setParameter('fromDate', $from)
            ->setParameter('toDate', $to)
            ->getResult();
    }
}

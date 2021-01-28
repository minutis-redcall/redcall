<?php

namespace App\Repository;

use App\Entity\Report;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Report|null find($id, $lockMode = null, $lockVersion = null)
 * @method Report|null findOneBy(array $criteria, array $orderBy = null)
 * @method Report[]    findAll()
 * @method Report[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function save(Report $report)
    {
        $this->_em->persist($report);
        $this->_em->flush();
    }

    public function getCommunicationReportsBetween(\DateTime $from, \DateTime $to) : array
    {
        /*
        return $this->createQueryBuilder('r')
                    ->join('r.communication', 'c')
                    ->where('c.createdAt BETWEEN :from AND :to')
                    ->join('r.repartitions', 'p')
                    ->join('p.structure', 's')
                    ->join('s.parentStructure', 'q')
                    ->andWhere('s.id = 1044 or q.id = 1044')
                    ->setParameter('from', $from)
                    ->setParameter('to', $to)
                    ->getQuery()
                    ->getResult();
         */

        return $this->createQueryBuilder('r')
                    ->join('r.communication', 'c')
                    ->where('c.createdAt BETWEEN :from AND :to')
                    ->setParameter('from', $from)
                    ->setParameter('to', $to)
                    ->getQuery()
                    ->getResult();
    }
}

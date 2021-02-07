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

    public function getCommunicationReportsBetween(\DateTime $from, \DateTime $to, int $minMessages) : array
    {
        return $this->createQueryBuilder('r')
                    ->join('r.communication', 'c')
                    ->where('c.createdAt BETWEEN :from AND :to')
                    ->setParameter('from', $from->format('Y-m-d'))
                    ->setParameter('to', $to->format('Y-m-d'))
                    ->andWhere('r.messageCount + r.questionCount >= :min_messages')
                    ->setParameter('min_messages', $minMessages)
                    ->getQuery()
                    ->getResult();
    }
}

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

    public function getCommunicationReportsBetween(\DateTime $from, \DateTime $to, int $minMessages = 3) : array
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

    /**
     * Native SQL query to get costs report for specific structures within a date range.
     * Returns raw data grouped by structure, campaign, and communication type.
     *
     * @param array $structureIds
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     */
    public function getCostsReportByStructures(array $structureIds, \DateTime $from, \DateTime $to): array
    {
        if (empty($structureIds)) {
            return [];
        }

        $conn = $this->_em->getConnection();

        // Build placeholders for structure IDs
        $placeholders = implode(',', array_fill(0, count($structureIds), '?'));

        $sql = "
            SELECT 
                s.id AS structure_id,
                s.name AS structure_name,
                camp.id AS campaign_id,
                camp.label AS campaign_label,
                camp.created_at AS campaign_date,
                comm.type AS communication_type,
                COUNT(DISTINCT comm.id) AS communications_count,
                SUM(rr.message_count) AS messages,
                SUM(rr.question_count) AS questions,
                SUM(rr.answer_count) AS answers,
                SUM(rr.error_count) AS errors,
                rr.costs AS costs_json
            FROM report_repartition rr
            INNER JOIN report r ON rr.report_id = r.id
            INNER JOIN communication comm ON r.id = comm.report_id
            INNER JOIN campaign camp ON comm.campaign_id = camp.id
            INNER JOIN structure s ON rr.structure_id = s.id
            WHERE rr.structure_id IN ({$placeholders})
              AND comm.created_at BETWEEN ? AND ?
            GROUP BY s.id, s.name, camp.id, camp.label, camp.created_at, comm.type, rr.costs
            ORDER BY s.name, camp.created_at DESC
        ";

        $params = array_merge(
            $structureIds,
            [$from->format('Y-m-d 00:00:00'), $to->format('Y-m-d 23:59:59')]
        );

        return $conn->fetchAllAssociative($sql, $params);
    }

    /**
     * Native SQL query to get monthly cost totals for specific structures over multiple months.
     *
     * @param array $structureIds
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     */
    public function getMonthlyTotalsByStructures(array $structureIds, \DateTime $from, \DateTime $to): array
    {
        if (empty($structureIds)) {
            return [];
        }

        $conn = $this->_em->getConnection();

        // Build placeholders for structure IDs
        $placeholders = implode(',', array_fill(0, count($structureIds), '?'));

        $sql = "
            SELECT 
                DATE_FORMAT(comm.created_at, '%Y-%m') AS month_key,
                s.id AS structure_id,
                s.name AS structure_name,
                rr.costs AS costs_json
            FROM report_repartition rr
            INNER JOIN report r ON rr.report_id = r.id
            INNER JOIN communication comm ON r.id = comm.report_id
            INNER JOIN structure s ON rr.structure_id = s.id
            WHERE rr.structure_id IN ({$placeholders})
              AND comm.created_at BETWEEN ? AND ?
            ORDER BY month_key DESC, s.name
        ";

        $params = array_merge(
            $structureIds,
            [$from->format('Y-m-d 00:00:00'), $to->format('Y-m-d 23:59:59')]
        );

        return $conn->fetchAllAssociative($sql, $params);
    }
}

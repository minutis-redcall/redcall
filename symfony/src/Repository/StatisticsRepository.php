<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * Warning: opposite to other repositories, this one
 * only use native queries for performance reasons.
 */
class StatisticsRepository
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function getNumberOfCampaigns(\DateTime $from, \DateTime $to): array
    {
        return $this->entityManager->getConnection()->fetchAssoc('
            SELECT COUNT(*) as created, SUM(IF(c.active, 1, 0)) as active
            FROM campaign c
            WHERE c.created_at BETWEEN :from AND :to
        ', [
            'from' => $from->format('Y-m-d 00:00:00'),
            'to' => $to->format('Y-m-d 23:59:59'),
        ]);
    }

    public function getEmailAndPhoneNumberMissings()
    {
        $sql = "
            select count(*) as total, 
                   sum(if(volunteer.email is null and volunteer.phone_number is not null, 1, 0)) as email_null,
                   sum(if(volunteer.phone_number is null and volunteer.email is not null, 1, 0)) as phone_null,
                   sum(if(volunteer.email is null and volunteer.phone_number is null, 1, 0)) as both_null,
                   sum(if(volunteer.phone_number is null or volunteer.email is null, 1, 0)) as one_is_null
            from volunteer
            where enabled = 1
            and locked = 0
        ";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('total', 'total', 'integer')
            ->addScalarResult('email_null', 'email_null', 'integer')
            ->addScalarResult('phone_null', 'phone_null', 'integer')
            ->addScalarResult('both_null', 'both_null', 'integer')
            ->addScalarResult('one_is_null', 'one_is_null', 'integer');

        return $this->entityManager
            ->createNativeQuery($sql, $rsm)
            ->getSingleResult();
    }

    /**
     * Return first and last pegass update for volunteers
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getVolunteerPegassUpdate()
    {
        $sql = "select min(volunteer.last_pegass_update) oldest_update, max(volunteer.last_pegass_update) newest_update from volunteer where volunteer.enabled = 1 and locked=0";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('oldest_update', 'oldest_update', 'datetime')
            ->addScalarResult('newest_update', 'newest_update', 'datetime');

        return $this->entityManager
            ->createNativeQuery($sql, $rsm)
            ->getSingleResult();
    }

    /**
     * Return first and last pegass update for structures
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getStructurePegassUpdate()
    {
        $sql = "select min(structure.last_pegass_update) oldest_update, max(structure.last_pegass_update) newest_update from structure where structure.enabled = 1";

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('oldest_update', 'oldest_update', 'datetime')
            ->addScalarResult('newest_update', 'newest_update', 'datetime');

        return $this->entityManager
            ->createNativeQuery($sql, $rsm)
            ->getSingleResult();
    }

    /**
     * Return all sent messages and group by kind (communication.type)
     *
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     */
    public function getNumberOfSentMessagesByKind(\DateTime $from, \DateTime $to)
    {
        $sql = 'select c.type, count(*)
                from message m join communication c
                on m.communication_id = c.id
                where c.created_at > :fromDate
                and c.created_at < :toDate
                group by c.type;';

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('type', 'type')
            ->addScalarResult('count(*)', 'count', 'integer');

        return $this->entityManager
            ->createNativeQuery($sql, $rsm)
            ->setParameter('fromDate', $from)
            ->setParameter('toDate', $to)
            ->getResult();
    }

    /**
     * Return all triggered volounteers
     * @param \DateTime $from
     * @param \DateTime $to
     * @return array
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getNumberOfTriggeredVolounteers(\DateTime $from, \DateTime $to)
    {
        $sql = 'select count(distinct m.volunteer_id) volounteers
                from message m
                join communication c on m.communication_id = c.id
                where c.created_at > :fromDate
                and c.created_at <= :toDate;';

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('volounteers', 'volounteers', 'integer');

        return $this->entityManager
            ->createNativeQuery($sql, $rsm)
            ->setParameter('fromDate', $from)
            ->setParameter('toDate', $to)
            ->getSingleResult();
    }

    /**
     * Return all answers received
     *
     * @return QueryBuilder
     * @throws NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function getNumberOfAnswersReceived(\DateTime $from, \DateTime $to)
    {
        $sql = 'select count(*) answers from message m
                join communication c on m.communication_id = c.id
                join answer a on a.message_id = m.id
                where c.created_at > :fromDate
                and c.created_at <= :toDate';

        $rsm = new ResultSetMapping();
        $rsm->addScalarResult('answers', 'answers', 'integer');

        return $this->entityManager
            ->createNativeQuery($sql, $rsm)
            ->setParameter(':fromDate', $from)
            ->setParameter(':toDate', $to)
            ->getSingleResult();
    }


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

        return $this->entityManager->createNativeQuery($sql, $rsm)
            ->setParameter('fromDate', $from)
            ->setParameter('toDate', $to)
            ->getResult();
    }
}
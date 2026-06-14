<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\VolunteerAuditLog;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VolunteerAuditLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method VolunteerAuditLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method VolunteerAuditLog[]    findAll()
 * @method VolunteerAuditLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VolunteerAuditLogRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VolunteerAuditLog::class);
    }

    public function searchQueryBuilder(?string $criteria, bool $hideTechnical = false) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('l')
                   ->leftJoin('l.actor', 'a')
                   ->addOrderBy('l.createdAt', 'DESC')
                   ->addOrderBy('l.id', 'DESC');

        if ($hideTechnical) {
            // rows written by the automated sync carry no actor; hiding them
            // surfaces only manual anonymizes (admin UI, volunteer self-delete via space)
            $qb->andWhere('l.actor IS NOT NULL');
        }

        if (null !== $criteria && '' !== trim($criteria)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'l.actorLabel LIKE :criteria',
                    'l.targetExternalId LIKE :criteria',
                    'l.targetBoundUserId LIKE :criteria',
                    'a.username LIKE :criteria'
                )
            )->setParameter('criteria', sprintf('%%%s%%', trim($criteria)));
        }

        return $qb;
    }
}

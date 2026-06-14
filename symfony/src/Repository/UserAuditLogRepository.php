<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\UserAuditLog;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserAuditLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAuditLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAuditLog[]    findAll()
 * @method UserAuditLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserAuditLogRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAuditLog::class);
    }

    public function searchQueryBuilder(?string $criteria, bool $hideTechnical = false) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('l')
                   ->leftJoin('l.actor', 'a')
                   ->leftJoin('l.targetUser', 't')
                   ->addOrderBy('l.createdAt', 'DESC')
                   ->addOrderBy('l.id', 'DESC');

        if ($hideTechnical) {
            // rows written by automations (sync, CLI...) carry no actor
            $qb->andWhere('l.actor IS NOT NULL');
        }

        if (null !== $criteria && '' !== trim($criteria)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'l.actorLabel LIKE :criteria',
                    'l.targetUsername LIKE :criteria',
                    'l.targetExternalId LIKE :criteria',
                    'l.targetDisplayName LIKE :criteria',
                    'a.username LIKE :criteria',
                    't.username LIKE :criteria'
                )
            )->setParameter('criteria', sprintf('%%%s%%', trim($criteria)));
        }

        return $qb;
    }
}

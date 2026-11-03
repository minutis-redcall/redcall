<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Expirable;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Expirable|null find($id, $lockMode = null, $lockVersion = null)
 * @method Expirable|null findOneBy(array $criteria, array $orderBy = null)
 * @method Expirable[]    findAll()
 * @method Expirable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ExpirableRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expirable::class);
    }

    /**
     * Atomically records one attempt against a still-valid expirable.
     *
     * The increment happens in a single conditional UPDATE so that concurrent
     * requests cannot all read the same counter and bypass the limit: only
     * $maxAttempts requests can ever succeed, whatever the parallelism.
     *
     * Returns true if the attempt was recorded (expirable exists, has not
     * expired, and had attempts left), false otherwise.
     */
    public function incrementAttempts(string $uuid, int $maxAttempts) : bool
    {
        $affected = $this
            ->createQueryBuilder('e')
            ->update()
            ->set('e.attempts', 'e.attempts + 1')
            ->where('e.uuid = :uuid')
            ->andWhere('e.attempts < :max')
            ->andWhere('e.expiresAt > :now')
            ->setParameter('uuid', $uuid)
            ->setParameter('max', $maxAttempts)
            ->setParameter('now', (new \DateTime())->format('Y-m-d H:i:s'))
            ->getQuery()
            ->execute();

        return 1 === $affected;
    }

    /**
     * Counts still-valid expirables created since $since whose JSON data
     * contains $fragment (e.g. '"user_id":"<uuid>"').
     */
    public function countValidCreatedSinceContaining(\DateTime $since, string $fragment) : int
    {
        return (int) $this
            ->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.createdAt >= :since')
            ->andWhere('e.expiresAt > :now')
            ->andWhere('e.data LIKE :fragment')
            ->setParameter('since', $since->format('Y-m-d H:i:s'))
            ->setParameter('now', (new \DateTime())->format('Y-m-d H:i:s'))
            ->setParameter('fragment', '%'.addcslashes($fragment, '%_\\').'%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function refresh(Expirable $expirable) : void
    {
        $this->getEntityManager()->refresh($expirable);
    }

    public function removeByUuid(string $uuid) : void
    {
        $this
            ->createQueryBuilder('e')
            ->delete()
            ->where('e.uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->getQuery()
            ->execute();
    }

    public function clearExpired()
    {
        return $this
            ->createQueryBuilder('e')
            ->delete()
            ->where('e.expiresAt < :now')
            ->setParameter('now', (new \DateTime())->format('Y-m-d H:i:s'))
            ->getQuery()
            ->execute();
    }
}

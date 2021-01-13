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

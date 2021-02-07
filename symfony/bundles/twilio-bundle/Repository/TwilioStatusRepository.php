<?php

namespace Bundles\TwilioBundle\Repository;

use Bundles\TwilioBundle\Entity\TwilioStatus;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TwilioStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method TwilioStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method TwilioStatus[]    findAll()
 * @method TwilioStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TwilioStatusRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwilioStatus::class);
    }
}

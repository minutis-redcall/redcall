<?php

namespace Bundles\TwilioBundle\Repository;

use Bundles\TwilioBundle\Entity\TwilioCall;
use Bundles\TwilioBundle\Entity\TwilioMessage;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TwilioMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method TwilioMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method TwilioMessage[]    findAll()
 * @method TwilioMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TwilioCallRepository extends BaseTwilioRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwilioCall::class);
    }
}

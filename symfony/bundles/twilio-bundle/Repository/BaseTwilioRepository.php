<?php

namespace Bundles\TwilioBundle\Repository;

use Bundles\TwilioBundle\Entity\BaseTwilio;

class BaseTwilioRepository extends BaseRepository
{
    /**
     * @param int $retries
     *
     * @return BaseTwilio[]
     */
    public function findEntitiesWithoutPrice(int $retries): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.price IS NULL')
            ->andWhere('t.sid IS NOT NULL')
            ->andWhere('t.retry < :retries')
            ->setParameter('retries', $retries)
            ->getQuery()
            ->getResult();
    }
}
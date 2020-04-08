<?php

namespace Bundles\TwilioBundle\Repository;

use Bundles\TwilioBundle\Entity\BaseTwilio;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class BaseTwilioRepository extends ServiceEntityRepository
{
    public function save(BaseTwilio $entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush();
    }

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
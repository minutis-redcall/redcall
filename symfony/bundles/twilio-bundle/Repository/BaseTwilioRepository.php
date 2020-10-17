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

    /**
     * @param callable $callback
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function foreach(callable $callback)
    {
        $count = $this->createQueryBuilder('t')
                      ->select('COUNT(t.id)')
                      ->getQuery()
                      ->getSingleScalarResult();

        $offset = 0;
        while ($offset < $count) {
            $qb = $this->createQueryBuilder('t')
                       ->setFirstResult($offset)
                       ->setMaxResults(100);

            $iterator = $qb->getQuery()->iterate();

            while (($row = $iterator->next()) !== false) {
                $entity = reset($row);

                if (false === $callback($entity)) {
                    break;
                }

                $this->_em->persist($entity);
            }

            $this->_em->flush();
            $this->_em->clear();

            $offset += 100;
        }
    }
}
<?php

namespace Bundles\PegassCrawlerBundle\Repository;

use Bundles\PegassCrawlerBundle\Entity\Pegass;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

/**
 * @method Pegass|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pegass|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pegass[]    findAll()
 * @method Pegass[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PegassRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Pegass::class);
    }

    /**
     * @param string $type
     *
     * @return Pegass[]
     */
    public function getEntities(string $type): array
    {
        return $this->findBy([
            'type' => $type,
        ]);
    }

    /**
     * @param string $type
     *
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countEntities(string $type): int
    {
        return $this->createQueryBuilder('p')
                    ->select('COUNT(p.id)')
                    ->where('p.type = :type')
                    ->setParameter('type', $type)
                    ->getQuery()
                    ->getSingleScalarResult();
    }

    public function getEntity(string $type,
        string $identifier = null,
        bool $onlyEnabled = true): ?Pegass
    {
        $filters['type'] = $type;

        if ($onlyEnabled) {
            $filters['enabled'] = true;
        }

        if ($identifier) {
            $filters['identifier'] = $identifier;
        }

        return $this->findOneBy($filters);
    }

    /**
     * @param int $limit
     *
     * @return array
     */
    public function findExpiredEntities(int $limit): array
    {
        return $this->createQueryBuilder('p')
                    ->where('p.type = :type_area AND p.updatedAt < :expire_area')
                    ->setParameter('type_area', Pegass::TYPE_AREA)
                    ->setParameter('expire_area', $this->getExpireDate(Pegass::TYPE_AREA))
                    ->orWhere('p.type = :type_department AND p.updatedAt < :expire_department')
                    ->setParameter('type_department', Pegass::TYPE_DEPARTMENT)
                    ->setParameter('expire_department', $this->getExpireDate(Pegass::TYPE_DEPARTMENT))
                    ->orWhere('p.type = :type_organization AND p.updatedAt < :expire_organization')
                    ->setParameter('type_organization', Pegass::TYPE_STRUCTURE)
                    ->setParameter('expire_organization', $this->getExpireDate(Pegass::TYPE_STRUCTURE))
                    ->orWhere('p.type = :type_volunteer AND p.updatedAt < :expire_volunteer')
                    ->setParameter('type_volunteer', Pegass::TYPE_VOLUNTEER)
                    ->setParameter('expire_volunteer', $this->getExpireDate(Pegass::TYPE_VOLUNTEER))
                    ->orderBy('p.type', 'ASC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @param string $type
     * @param array  $identifiers
     *
     * @return array
     */
    public function removeMissingEntities(string $type, array $identifiers)
    {
        $qb = $this->createQueryBuilder('p');

        $qb->update(Pegass::class, 'p')
           ->set('p.enabled', $qb->expr()->literal(false))
           ->where('p.type = :type')
           ->setParameter('type', $type)
           ->andWhere('p.identifier NOT IN (:identifiers)')
           ->setParameter('identifiers', $identifiers);

        return $qb
            ->getQuery()
            ->execute();
    }

    /**
     * @param string $type
     * @param array  $identifiers
     * @param string $parentIdentifier
     *
     * @return Pegass[]
     */
    public function findMissingEntities(string $type, array $identifiers, string $parentIdentifier): array
    {
        return $this->createQueryBuilder('p')
                    ->where('p.type = :type')
                    ->setParameter('type', $type)
                    ->andWhere('p.identifier NOT IN (:identifiers)')
                    ->setParameter('identifiers', $identifiers)
                    ->andWhere('p.parentIdentifier LIKE :parentIdentifier')
                    ->setParameter('parentIdentifier', sprintf('%%%s%%', $parentIdentifier))
                    ->getQuery()
                    ->execute();
    }

    /**
     * @param string $type
     *
     * @return array
     */
    public function listIdentifiers(string $type): array
    {
        $rows = $this->createQueryBuilder('p')
                     ->select('p.identifier')
                     ->where('p.type = :type')
                     ->setParameter('type', $type)
                     ->getQuery()
                     ->getArrayResult();

        return array_column($rows, 'identifier');
    }

    /**
     * @param string   $type
     * @param callable $callback
     * @param bool     $onlyEnabled
     *
     * @throws MappingException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function foreach(string $type, callable $callback, bool $onlyEnabled = true)
    {
        $count = $this->createQueryBuilder('p')
                      ->select('COUNT(p.id)')
                      ->where('p.type = :type')
                      ->setParameter('type', $type)
                      ->getQuery()
                      ->getSingleScalarResult();

        $offset = 0;
        while ($offset < $count) {
            $qb = $this->createQueryBuilder('p')
                       ->where('p.type = :type')
                       ->setParameter('type', $type);

            if ($onlyEnabled) {
                $qb->andWhere('p.enabled = true');
            }

            $qb->setFirstResult($offset)
               ->setMaxResults(100);

            $iterator = $qb->getQuery()->iterate();

            while (($row = $iterator->next()) !== false) {
                /* @var Pegass $entity */
                $entity = reset($row);

                // Do not proceed an entity that is not imported yet
                if (!$entity->getContent()) {
                    continue;
                }

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

    /**
     * @param Pegass $entity
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Pegass $entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }

    /**
     * @param string $type
     *
     * @return DateTime
     */
    private function getExpireDate(string $type): DateTime
    {
        return DateTime::createFromFormat('U', time() - Pegass::TTL[$type]);
    }
}

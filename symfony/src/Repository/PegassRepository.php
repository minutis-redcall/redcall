<?php

namespace App\Repository;

use App\Entity\Pegass;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

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

    public function getEntities(string $type) : array
    {
        return $this->findBy([
            'type' => $type,
        ]);
    }

    public function countEntities(string $type) : int
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
        bool $onlyEnabled = true) : ?Pegass
    {
        $qb = $this->createQueryBuilder('p')
                   ->andWhere('p.type = :type')
                   ->setParameter('type', $type);

        if ($onlyEnabled) {
            $qb->andWhere('p.enabled = true');
        }

        if ($identifier) {
            $qb->andWhere('p.identifier = :identifier')
               ->setParameter('identifier', $identifier);
        }

        return $qb->getQuery()
                  ->disableResultCache()
                  ->getOneOrNullResult();
    }

    public function findExpiredEntities(int $limit) : array
    {
        return $this->createQueryBuilder('p')
                    ->orWhere('p.type = :type_organization AND p.updatedAt < :expire_organization')
                    ->setParameter('type_organization', Pegass::TYPE_STRUCTURE)
                    ->setParameter('expire_organization', $this->getExpireDate(Pegass::TYPE_STRUCTURE))
                    ->orWhere('p.type = :type_volunteer AND p.updatedAt < :expire_volunteer')
                    ->setParameter('type_volunteer', Pegass::TYPE_VOLUNTEER)
                    ->setParameter('expire_volunteer', $this->getExpireDate(Pegass::TYPE_VOLUNTEER))
                    ->orderBy('p.updatedAt', 'ASC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @return Pegass[]
     */
    public function findMissingEntities(string $type, array $identifiers, ?string $parentIdentifier = null) : array
    {
        $qb = $this->createQueryBuilder('p')
                   ->where('p.type = :type')
                   ->setParameter('type', $type)
                   ->andWhere('p.identifier NOT IN (:identifiers)')
                   ->setParameter('identifiers', $identifiers)
                   ->andWhere('p.enabled = true');

        if ($parentIdentifier) {
            $qb->andWhere('p.parentIdentifier = :parentIdentifier')
               ->setParameter('parentIdentifier', $parentIdentifier);
        }

        return $qb
            ->getQuery()
            ->execute();
    }

    /**
     * @return Pegass[]
     */
    public function findAllChildrenEntities(string $type, string $parentIdentifier) : array
    {
        return $this->createQueryBuilder('p')
                    ->where('p.type = :type')
                    ->setParameter('type', $type)
                    ->andWhere('p.parentIdentifier LIKE :parentIdentifier')
                    ->setParameter('parentIdentifier', sprintf('%%%s%%', $parentIdentifier))
                    ->getQuery()
                    ->execute();
    }

    public function foreach(string $type, callable $callback, bool $onlyEnabled = true)
    {
        $qb = $this->createQueryBuilder('p')
                   ->select('COUNT(p.id)')
                   ->where('p.type = :type')
                   ->setParameter('type', $type);

        if ($onlyEnabled) {
            $qb->andWhere('p.enabled = true');
        }

        $count = $qb->getQuery()
                    ->getSingleScalarResult();

        $qb->select('p.id')
           ->setMaxResults(100);

        $offset = 0;
        $stop   = false;
        while ($offset < $count) {
            $qb->setFirstResult($offset);

            $iterator = $qb->getQuery()->iterate();

            while (($row = $iterator->next()) !== false) {
                /* @var Pegass $entity */
                $entity = $this->find(reset($row)['id']);

                // Do not proceed an entity that is not imported yet
                if (!$entity->getContent()) {
                    continue;
                }

                if (false === $callback($entity)) {
                    $stop = true;
                    break;
                }

                $this->_em->persist($entity);
                unset($entity);
            }

            $this->_em->flush();
            $this->_em->clear();

            if ($stop) {
                break;
            }

            $offset += 100;
        }
    }

    public function getAllEnabledEntities() : array
    {
        return $this
            ->createQueryBuilder('p')
            ->select('p.type', 'p.identifier')
            ->where('p.enabled = true')
            ->getQuery()
            ->getArrayResult();
    }

    public function getEnabledEntitiesQueryBuilder(?string $type, ?string $identifier) : QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder('p')
            ->where('p.enabled = true');

        if ($type) {
            $qb->andWhere('p.type = :type')
               ->setParameter('type', $type);
        }

        if ($identifier) {
            $qb->andWhere('p.identifier = :identifier')
               ->setParameter('identifier', $identifier);
        }

        return $qb;
    }

    public function save(Pegass $entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush($entity);
    }

    public function delete(Pegass $entity)
    {
        $this->_em->remove($entity);
        $this->_em->flush($entity);
    }

    private function getExpireDate(string $type) : DateTime
    {
        return DateTime::createFromFormat('U', time() - (Pegass::TTL[$type] * 24 * 60 * 60));
    }
}

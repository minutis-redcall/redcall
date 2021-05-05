<?php

namespace Bundles\PegassCrawlerBundle\Repository;

use Bundles\PegassCrawlerBundle\Entity\Pegass;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\Mapping\MappingException;
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
        $filters['type'] = $type;

        if ($onlyEnabled) {
            $filters['enabled'] = true;
        }

        if ($identifier) {
            $filters['identifier'] = $identifier;
        }

        return $this->findOneBy($filters);
    }

    public function findExpiredEntities(int $limit) : array
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
                    ->addOrderBy('p.updatedAt', 'ASC')
                    ->setMaxResults($limit)
                    ->getQuery()
                    ->getResult();
    }

    public function removeMissingEntities(string $type, array $identifiers, string $parentIdentifier = null)
    {
        $qb = $this->createQueryBuilder('p');

        $qb->update(Pegass::class, 'p')
           ->set('p.enabled', $qb->expr()->literal(false))
           ->where('p.type = :type')
           ->setParameter('type', $type)
           ->andWhere('p.identifier NOT IN (:identifiers)')
           ->setParameter('identifiers', $identifiers);

        if ($parentIdentifier) {
            $qb->andWhere('p.parentIdentifier = :parentIdentifier')
               ->setParameter('parentIdentifier', $parentIdentifier);
        }

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
    public function findMissingEntities(string $type, array $identifiers, string $parentIdentifier) : array
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
        $this->_em->flush();
    }

    private function getExpireDate(string $type) : DateTime
    {
        return DateTime::createFromFormat('U', time() - (Pegass::TTL[$type] * 24 * 60 * 60));
    }
}

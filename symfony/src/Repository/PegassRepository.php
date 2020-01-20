<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Pegass;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Pegass|null find($id, $lockMode = null, $lockVersion = null)
 * @method Pegass|null findOneBy(array $criteria, array $orderBy = null)
 * @method Pegass[]    findAll()
 * @method Pegass[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PegassRepository extends BaseRepository
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
    public function getEntities(string $type)
    {
        return $this->findBy([
            'type' => $type,
        ]);
    }

    /**
     * @param string      $type
     * @param string|null $identifier
     *
     * @return Pegass|null
     */
    public function getEntity(string $type, string $identifier = null): ?Pegass
    {
        $filters['type'] = $type;

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
     * @param string      $type
     * @param array       $identifiers
     * @param string|null $parentIdentifier
     *
     * @return array
     */
    public function removeMissingEntities(string $type, array $identifiers, string $parentIdentifier = null)
    {
        $qb = $this->createQueryBuilder('p')
                   ->delete(Pegass::class, 'p')
                   ->where('p.type = :type')
                   ->setParameter('type', $type)
                   ->andWhere('p.identifier NOT IN (:identifiers)')
                   ->setParameter('identifiers', $identifiers);

        if ($parentIdentifier) {
            $qb
                ->andWhere('p.parentIdentifier = :parentIdentifier')
                ->setParameter('parentIdentifier', $parentIdentifier);
        }

        return $qb
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
     *
     * @return int
     */
    public function foreach(string $type, callable $callback): int
    {
        $count = 0;

        $iterator = $this->createQueryBuilder('p')
                         ->where('p.type = :type')
                         ->setParameter('type', $type)
                         ->getQuery()
                         ->iterate();

        while (($row = $iterator->next()) !== false) {
            /* @var Pegass $entity */
            $entity = reset($row);

            if (!$entity->getContent()) {
                continue;
            }

            if (false === $callback($entity)) {
                break;
            }

            $this->save($entity);
            $this->_em->clear(Pegass::class);
            $count++;
        }

        return $count;
    }

    /**
     * @param string $type
     *
     * @return \DateTime
     */
    private function getExpireDate(string $type): \DateTime
    {
        return \DateTime::createFromFormat('U', time() - Pegass::TTL[$type]);
    }
}

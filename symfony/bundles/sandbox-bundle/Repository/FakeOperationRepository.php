<?php

namespace Bundles\SandboxBundle\Repository;

use Bundles\SandboxBundle\Base\BaseRepository;
use Bundles\SandboxBundle\Entity\FakeOperation;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FakeOperation|null find($id, $lockMode = null, $lockVersion = null)
 * @method FakeOperation|null findOneBy(array $criteria, array $orderBy = null)
 * @method FakeOperation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FakeOperationRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FakeOperation::class);
    }

    /**
     * @return FakeOperation[]
     */
    public function search(int $structureExternalId, string $criteria = null) : array
    {
        return $this
            ->createQueryBuilder('o')
            ->where('o.structureExternalId = :structure_external_id')
            ->setParameter('structure_external_id', $structureExternalId)
            ->andWhere('o.name LIKE :criteria')
            ->setParameter('criteria', sprintf('%%%s%%', $criteria))
            ->orderBy('o.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAll()
    {
        return $this->findBy([], ['updatedAt' => 'DESC']);
    }
}

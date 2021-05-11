<?php

namespace Bundles\SandboxBundle\Repository;

use Bundles\SandboxBundle\Base\BaseRepository;
use Bundles\SandboxBundle\Entity\FakeOperationResource;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FakeOperationResource|null find($id, $lockMode = null, $lockVersion = null)
 * @method FakeOperationResource|null findOneBy(array $criteria, array $orderBy = null)
 * @method FakeOperationResource[]    findAll()
 * @method FakeOperationResource[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FakeOperationResourceRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FakeOperationResource::class);
    }
}

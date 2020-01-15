<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Structure;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Structure|null find($id, $lockMode = null, $lockVersion = null)
 * @method Structure|null findOneBy(array $criteria, array $orderBy = null)
 * @method Structure[]    findAll()
 * @method Structure[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StructureRepository extends BaseRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Structure::class);
    }
}

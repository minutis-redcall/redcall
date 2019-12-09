<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Choice;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Choice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Choice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Choice[]    findAll()
 * @method Choice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChoiceRepository extends BaseRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Choice::class);
    }
}

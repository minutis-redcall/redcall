<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Choice;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method Choice|null find($id, $lockMode = null, $lockVersion = null)
 * @method Choice|null findOneBy(array $criteria, array $orderBy = null)
 * @method Choice[]    findAll()
 * @method Choice[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ChoiceRepository extends BaseRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Choice::class);
    }
}

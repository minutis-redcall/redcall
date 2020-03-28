<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\PrefilledAnswers;
use App\Entity\Structure;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

/**
 * @method PrefilledAnswers|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrefilledAnswers|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrefilledAnswers[]    findAll()
 * @method PrefilledAnswers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrefilledAnswersRepository extends BaseRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, PrefilledAnswers::class);
    }

    //    /**
    //     * @return PrefilledAnswers[] Returns an array of PrefilledAnswers objects
    //     */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('p.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?PrefilledAnswers
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */

    /**
     * Return all prefilled answers for a structure
     * @return QueryBuilder
     */
    public function getPrefilledAnswersByStructure(Structure $structure)
    {
        $qb = $this->createQueryBuilder('pa');
        $qb->where($qb->expr()->eq('pa.structure', ':structure'))
            ->setParameter('structure', $structure)
        ;

        return $qb;
    }
}

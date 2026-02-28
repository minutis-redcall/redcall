<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\PrefilledAnswers;
use App\Entity\Structure;
use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PrefilledAnswers|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrefilledAnswers|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrefilledAnswers[]    findAll()
 * @method PrefilledAnswers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrefilledAnswersRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PrefilledAnswers::class);
    }

    public function getPrefilledAnswersByStructure(Structure $structure)
    {
        $qb = $this->createQueryBuilder('pa')
                   ->join('pa.structure', 's')
                   ->where('s.id = :id')
                   ->setParameter('id', $structure->getId());

        return $qb;
    }

    public function findByUserForStructureAndGlobal(User $user)
    {
        $qb = $this->createQueryBuilder('pa')
                   ->leftJoin('pa.structure', 's')
                   ->where('s.id IS NULL OR s.id IN (:id)')
                   ->setParameter('id', $user->getStructures());

        return $qb->getQuery()->getResult();
    }

    public function getGlobalPrefilledAnswers()
    {
        return $this->createQueryBuilder('pa')
                    ->where('pa.structure is null');
    }
}

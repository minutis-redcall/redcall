<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\PrefilledAnswers;
use App\Entity\Structure;
use App\Entity\User;
use App\Security\Helper\Security;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method PrefilledAnswers|null find($id, $lockMode = null, $lockVersion = null)
 * @method PrefilledAnswers|null findOneBy(array $criteria, array $orderBy = null)
 * @method PrefilledAnswers[]    findAll()
 * @method PrefilledAnswers[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PrefilledAnswersRepository extends BaseRepository
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security, ManagerRegistry $registry)
    {
        parent::__construct($registry, PrefilledAnswers::class);

        $this->security = $security;
    }

    public function getPrefilledAnswersByStructure(Structure $structure)
    {
        $qb = $this->createQueryBuilder('pa')
                   ->join('pa.structure', 's')
                   ->where('s.id = :id')
                   ->setParameter('id', $structure->getId())
                   ->andWhere('s.platform = :platform')
                   ->setParameter('platform', $this->security->getPlatform());

        return $qb;
    }

    public function findByUserForStructureAndGlobal(User $user)
    {
        $qb = $this->createQueryBuilder('pa')
                   ->leftJoin('pa.structure', 's')
                   ->where('s.id IS NULL or s.id IN (:id)')
                   ->setParameter('id', $user->getStructures())
                   ->andWhere('s.platform IS NULL or s.platform = :platform')
                   ->setParameter('platform', $this->security->getPlatform());

        return $qb->getQuery()->getResult();
    }

    public function getGlobalPrefilledAnswers()
    {
        return $this->createQueryBuilder('pa')->where('pa.structure is null');
    }
}

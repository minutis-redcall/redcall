<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Structure;
use App\Entity\UserInformation;
use App\Entity\Volunteer;
use Doctrine\ORM\QueryBuilder;
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

    /**
     * @return array
     */
    public function listStructureIdentifiers(): array
    {
        $rows = $this->createQueryBuilder('s')
                     ->select('s.identifier')
                     ->getQuery()
                     ->getArrayResult();

        return array_column($rows, 'identifier');
    }

    /**
     * @param string $identifier
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function disableByIdentifier(string $identifier)
    {
        $this->createQueryBuilder('s')
             ->update()
             ->set('s.enabled = :enabled')
             ->setParameter('enabled', false)
             ->where('s.identifier = :identifier')
             ->setParameter('identifier', $identifier)
             ->getQuery()
             ->execute();
    }

    /**
     * This method perform nested search of all volunteer's structures
     *
     * Each structure can have children structures:
     * - an AS can call its volunteers
     * - an UL can call some AS + its volunteers
     * - a DT can call its ULs + all their ASs
     *
     * @param Volunteer $volunteer
     *
     * @return array
     */
    public function findCallableStructuresForVolunteer(Volunteer $volunteer): array
    {
        $structures = $this->createQueryBuilder('s')
                           ->select('
                                s.id as s1,
                                ss.id as s2,
                                sss.id as s3,
                                ssss.id as s4,
                                sssss.id as s5'
                           )
                           ->innerJoin('s.volunteers', 'v')
                           ->leftJoin('s.childrenStructures', 'ss')
                           ->leftJoin('ss.childrenStructures', 'sss')
                           ->leftJoin('sss.childrenStructures', 'ssss')
                           ->leftJoin('ssss.childrenStructures', 'sssss')
                           ->where('v.id = :id')
                           ->andWhere('s.enabled IS NULL OR s.enabled = true')
                           ->andWhere('ss.enabled IS NULL OR ss.enabled = true')
                           ->andWhere('sss.enabled IS NULL OR sss.enabled = true')
                           ->andWhere('ssss.enabled IS NULL OR ssss.enabled = true')
                           ->andWhere('sssss.enabled IS NULL OR sssss.enabled = true')
                           ->setParameter('id', $volunteer->getId())
                           ->getQuery()
                           ->getArrayResult();

        $ids = array_filter(array_unique(array_merge(
            array_column($structures, 's1'),
            array_column($structures, 's2'),
            array_column($structures, 's3'),
            array_column($structures, 's4'),
            array_column($structures, 's5')
        )));

        return $this->createQueryBuilder('s')
                    ->where('s.id IN (:ids)')
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @param array $structures
     *
     * @return array
     */
    public function countVolunteersInStructures(array $structures): array
    {
        $ids = array_map(function (Structure $structure) {
            return $structure->getId();
        }, $structures);

        $rows = $this->createQueryBuilder('s')
                     ->select('s.id as structure_id, COUNT(v.id) as count')
                     ->join('s.volunteers', 'v')
                     ->where('s.id IN (:ids)')
                     ->andWhere('s.enabled = true')
                     ->setParameter('ids', $ids)
                     ->andWhere('v.enabled = true')
                     ->groupBy('s.id')
                     ->getQuery()
                     ->getArrayResult();

        return array_combine(
            array_column($rows, 'structure_id'),
            array_column($rows, 'count')
        );
    }

    /**
     * @param UserInformation $user
     *
     * @return array
     */
    public function getTagCountByStructuresForUser(UserInformation $user): array
    {
        return $this->createQueryBuilder('s')
                    ->select('s.id as structure_id, t.id as tag_id, COUNT(v.id) as count')
                    ->join('s.users', 'u')
                    ->join('s.volunteers', 'v')
                    ->join('v.tags', 't')
                    ->where('u.id = :id')
                    ->andWhere('s.enabled = true')
                    ->setParameter('id', $user->getId())
                    ->orderBy('t.id', 'ASC')
                    ->groupBy('s.id', 't.id')
                    ->getQuery()
                    ->getArrayResult();
    }

    /**
     * @param UserInformation $userInformation
     *
     * @return QueryBuilder
     */
    public function getStructuresForUserQueryBuilder(UserInformation $userInformation): QueryBuilder
    {
        return $this->createQueryBuilder('s')
                    ->join('s.users', 'u')
                    ->where('u.id = :id')
                    ->setParameter('id', $userInformation->getId())
                    ->andWhere('s.enabled = true')
                    ->orderBy('s.identifier', 'asc');
    }

    /**
     * @param string $criteria
     *
     * @return QueryBuilder
     */
    public function searchAllQueryBuilder(?string $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s');

        if ($criteria) {
            $qb->where('s.identifier LIKE :criteria OR s.name LIKE :criteria')
               ->setParameter('criteria', sprintf('%%%s%%', $criteria));
        }

        return $qb;
    }

    /**
     * @param UserInformation $user
     * @param string          $criteria
     *
     * @return QueryBuilder
     */
    public function searchForUser(UserInformation $user, ?string $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s')
                   ->join('s.users', 'u')
                   ->where('u.id = :user_id')
                   ->setParameter('user_id', $user->getId());

        if ($criteria) {
            $qb->where('s.identifier LIKE :criteria OR s.name LIKE :criteria')
               ->setParameter('criteria', sprintf('%%%s%%', $criteria));
        }

        return $qb;
    }


}

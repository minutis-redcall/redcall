<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Structure;
use App\Entity\Volunteer;
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

}

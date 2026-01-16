<?php

namespace App\Repository;

use App\Entity\VolunteerGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VolunteerGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method VolunteerGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method VolunteerGroup[]    findAll()
 * @method VolunteerGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VolunteerGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VolunteerGroup::class);
    }

    public function save(VolunteerGroup $entity, bool $flush = false) : void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(VolunteerGroup $entity, bool $flush = false) : void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getVolunteerGroups(int $campaignId) : array
    {
        $rows = $this->createQueryBuilder('vg')
                     ->select('v.id as volunteerId, vg.groupIndex')
                     ->join('vg.volunteer', 'v')
                     ->where('vg.campaign = :campaignId')
                     ->setParameter('campaignId', $campaignId)
                     ->getQuery()
                     ->getArrayResult();

        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['volunteerId']][] = $row['groupIndex'];
        }

        return $groups;
    }

    public function countVolunteerGroups(int $campaignId) : int
    {
        return $this->createQueryBuilder('vg')
                    ->select('COUNT(vg.volunteer)')
                    ->where('vg.campaign = :campaignId')
                    ->setParameter('campaignId', $campaignId)
                    ->getQuery()
                    ->getSingleScalarResult();
    }
}

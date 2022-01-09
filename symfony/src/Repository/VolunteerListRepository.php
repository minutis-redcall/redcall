<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\User;
use App\Entity\VolunteerList;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VolunteerList|null find($id, $lockMode = null, $lockVersion = null)
 * @method VolunteerList|null findOneBy(array $criteria, array $orderBy = null)
 * @method VolunteerList[]    findAll()
 * @method VolunteerList[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VolunteerListRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VolunteerList::class);
    }

    /**
     * @return VolunteerList[]
     */
    public function findVolunteerListsForUser(string $platform, User $user) : array
    {
        return $this->createQueryBuilder('l')
                    ->join('l.structure', 's')
                    ->join('s.users', 'u')
                    ->where('u.id = :id')
                    ->setParameter('id', $user->getId())
                    ->andWhere('s.enabled = true')
                    ->andWhere('s.platform = :platform')
                    ->setParameter('platform', $platform)
                    ->orderBy('s.externalId', 'asc')
                    ->getQuery()
                    ->getResult();
    }
}

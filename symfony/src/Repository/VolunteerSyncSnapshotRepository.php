<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\VolunteerSyncSnapshot;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VolunteerSyncSnapshot|null find($id, $lockMode = null, $lockVersion = null)
 * @method VolunteerSyncSnapshot|null findOneBy(array $criteria, array $orderBy = null)
 * @method VolunteerSyncSnapshot[]    findAll()
 */
class VolunteerSyncSnapshotRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VolunteerSyncSnapshot::class);
    }

    public function findOneByExternalId(string $externalId) : ?VolunteerSyncSnapshot
    {
        return $this->findOneBy(['externalId' => ltrim($externalId, '0')]);
    }
}

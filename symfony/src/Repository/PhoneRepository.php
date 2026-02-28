<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Phone;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Phone|null find($id, $lockMode = null, $lockVersion = null)
 * @method Phone|null findOneBy(array $criteria, array $orderBy = null)
 * @method Phone[]    findAll()
 * @method Phone[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PhoneRepository extends BaseRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Phone::class);
    }

    public function findOneByPhoneNumber(string $phoneNumber) : ?Phone
    {
        return $this->createQueryBuilder('p')
                    ->join('p.volunteers', 'v')
                    ->where('p.e164 = :phoneNumber')
                    ->setParameter('phoneNumber', $phoneNumber)
                    ->andWhere('p.preferred = true')
                    ->andWhere('v.enabled = true')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
    }

    /**
     * Used in VolunteerController::phoneRemove
     */
    public function findOneByVolunteerAndE164(string $externalId, string $e164)
    {
        return $this->createQueryBuilder('p')
                    ->join('p.volunteers', 'v')
                    ->where('p.e164 = :phoneNumber')
                    ->setParameter('phoneNumber', $e164)
                    ->andWhere('v.externalId = :externalId')
                    ->setParameter('externalId', $externalId)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();
    }
}

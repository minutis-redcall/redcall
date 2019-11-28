<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Communication;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Communication|null find($id, $lockMode = null, $lockVersion = null)
 * @method Communication|null findOneBy(array $criteria, array $orderBy = null)
 * @method Communication[]    findAll()
 * @method Communication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommunicationRepository extends BaseRepository
{
    /**
     * CommunicationRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Communication::class);
    }

    /**
     * @param Communication $communication
     * @param string        $newName
     */
    public function changeName(Communication $communication, string $newName): void
    {
        $communication->setLabel($newName);
        $this->save($communication);
    }


    /**
     * Get prefixes that cannot be used.
     */
    public function getTakenPrefixes()
    {
        $prefixes = array_column($this->createQueryBuilder('co')
            ->select('co.prefix')
            ->distinct()
            ->innerJoin('App:Campaign', 'ca', 'WITH', 'ca = co.campaign')
            ->where('co.prefix IS NOT NULL')
            ->andWhere('ca.active = 1')
            ->getQuery()
            ->getArrayResult(), 'prefix');

        sort($prefixes);

        return $prefixes;
    }
}

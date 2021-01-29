<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Communication;
use Doctrine\Bundle\DoctrineBundle\Registry;

/**
 * @method Communication|null find($id, $lockMode = null, $lockVersion = null)
 * @method Communication|null findOneBy(array $criteria, array $orderBy = null)
 * @method Communication[]    findAll()
 * @method Communication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommunicationRepository extends BaseRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Communication::class);
    }

    /**
     * @param Communication $communication
     * @param string        $newName
     */
    public function changeName(Communication $communication, string $newName) : void
    {
        $communication->setLabel($newName);
        $this->save($communication);
    }

    public function findCommunicationIdsRequiringReports(\DateTime $date) : array
    {
        $rows = $this->createQueryBuilder('c')
                     ->select('c.id')
                     ->where('c.lastActivityAt < :date OR c.lastActivityAt IS NULL AND c.createdAt < :date')
                     ->setParameter('date', $date)
                     ->andWhere('c.report IS NULL')
                     ->getQuery()
                     ->getArrayResult();

        return array_column($rows, 'id');
    }

    public function clearEntityManager()
    {
        $this->_em->clear();
    }

    public function getCommunicationStructures(Communication $communication) : array
    {
        $rows = $this->createQueryBuilder('c')
                     ->select('s.id, COUNT(DISTINCT v) AS volunteer_count')
                     ->join('c.messages', 'm')
                     ->join('m.volunteer', 'v')
                     ->join('v.structures', 's')
                     ->where('c.id = :communication_id')
                     ->setParameter('communication_id', $communication->getId())
                     ->orderBy('volunteer_count', 'DESC')
                     ->groupBy('s.id')
                     ->getQuery()
                     ->getArrayResult();

        return array_column($rows, 'id');
    }
}

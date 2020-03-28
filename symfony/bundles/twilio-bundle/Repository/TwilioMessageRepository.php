<?php

namespace Bundles\TwilioBundle\Repository;

use Bundles\TwilioBundle\Entity\TwilioMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TwilioMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method TwilioMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method TwilioMessage[]    findAll()
 * @method TwilioMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TwilioMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TwilioMessage::class);
    }

    public function save($entity)
    {
        $this->_em->persist($entity);
        $this->_em->flush();
    }

    /**
     * @return TwilioMessage[]
     */
    public function findMessagesWithoutPrice(): array
    {
        return $this->createQueryBuilder('m')
                    ->where('m.price IS NULL')
                    ->andWhere('m.sid IS NOT NULL')
                    ->andWhere('m.status NOT IN :status')
                    ->setParameter('status', [TwilioMessage::STATUS_ERROR, TwilioMessage::STATUS_FAILED])
                    ->getQuery()
                    ->getResult();
    }

    // /**
    //  * @return TwilioOutbound[] Returns an array of TwilioOutbound objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TwilioOutbound
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

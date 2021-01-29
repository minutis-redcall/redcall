<?php

namespace Bundles\SandboxBundle\Repository;

use Bundles\SandboxBundle\Entity\FakeCall;
use Bundles\SandboxBundle\Entity\FakeSms;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FakeSms|null find($id, $lockMode = null, $lockVersion = null)
 * @method FakeSms|null findOneBy(array $criteria, array $orderBy = null)
 * @method FakeSms[]    findAll()
 * @method FakeSms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FakeCallRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FakeCall::class);
    }

    public function findAllPhones() : array
    {
        return $this->createQueryBuilder('c')
                    ->select('
                        c.phoneNumber, 
                        MAX(c.createdAt) as lastMsg, 
                        COUNT(c.phoneNumber) as countMsg
                    ')
                    ->groupBy('c.phoneNumber')
                    ->getQuery()
                    ->getArrayResult();
    }

    public function save(FakeCall $fakeCall)
    {
        $this->_em->persist($fakeCall);
        $this->_em->flush();
    }

    public function findMessagesForPhone(string $phoneNumber)
    {
        return $this->createQueryBuilder('c')
                    ->where('c.phoneNumber = :phoneNumber')
                    ->setParameter('phoneNumber', $phoneNumber)
                    ->orderBy('c.id', 'DESC')
                    ->getQuery()
                    ->getResult();
    }

    public function truncate()
    {
        $this->createQueryBuilder('c')->delete()->getQuery()->execute();
    }
}

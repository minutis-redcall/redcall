<?php

namespace Bundles\SandboxBundle\Repository;

use Bundles\SandboxBundle\Entity\FakeEmail;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FakeEmail|null find($id, $lockMode = null, $lockVersion = null)
 * @method FakeEmail|null findOneBy(array $criteria, array $orderBy = null)
 * @method FakeEmail[]    findAll()
 * @method FakeEmail[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FakeEmailRepository extends ServiceEntityRepository
{
    /**
     * AnswerRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FakeEmail::class);
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $body
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function store(string $to, string $subject, string $body)
    {
        $fake = new FakeEmail();
        $fake->setEmail($to);
        $fake->setSubject($subject);
        $fake->setBody($body);
        $fake->setCreatedAt(new DateTime());

        $this->_em->persist($fake);
        $this->_em->flush();
    }

    /**
     * @return array
     */
    public function findAllEmails() : array
    {
        return $this->createQueryBuilder('e')
                    ->select('
                        e.email, 
                        MAX(e.createdAt) as lastMsg, 
                        COUNT(e.email) as countMsg
                    ')
                    ->groupBy('e.email')
                    ->getQuery()
                    ->getArrayResult();

    }

    /**
     * @param string $phoneNumber
     *
     * @return array
     */
    public function findMessagesForEmail(string $email) : array
    {
        return $this->createQueryBuilder('e')
                    ->where('e.email = :email')
                    ->setParameter('email', $email)
                    ->orderBy('e.id', 'ASC')
                    ->getQuery()
                    ->getResult();
    }

    public function truncate()
    {
        $this->createQueryBuilder('s')->delete()->getQuery()->execute();
    }
}

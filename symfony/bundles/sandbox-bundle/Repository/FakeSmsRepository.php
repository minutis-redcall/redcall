<?php

namespace Bundles\SandboxBundle\Repository;

use App\Entity\Volunteer;
use Bundles\SandboxBundle\Entity\FakeSms;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method FakeSms|null find($id, $lockMode = null, $lockVersion = null)
 * @method FakeSms|null findOneBy(array $criteria, array $orderBy = null)
 * @method FakeSms[]    findAll()
 * @method FakeSms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FakeSmsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FakeSms::class);
    }

    /**
     * @return array
     */
    public function findAllPhones(): array
    {
        return $this->createQueryBuilder('s')
                    ->select('
                        s.phoneNumber, 
                        MAX(s.createdAt) as lastMsg, 
                        COUNT(s.phoneNumber) as countMsg
                    ')
                    ->groupBy('s.phoneNumber')
                    ->getQuery()
                    ->getArrayResult();

    }

    public function findMessagesForPhoneNumber(string $phoneNumber): array
    {
        return $this->createQueryBuilder('s')
                    ->where('s.phoneNumber = :phoneNumber')
                    ->setParameter('phoneNumber', $phoneNumber)
                    ->orderBy('s.id', 'ASC')
                    ->getQuery()
                    ->getResult();
    }

    public function save(Volunteer $volunteer, string $content, string $direction)
    {
        $fakeSms = new FakeSms();
        $fakeSms->setName(sprintf('%s %s', $volunteer->getFirstName(), $volunteer->getLastName()));
        $fakeSms->setPhoneNumber($volunteer->getPhoneNumber());
        $fakeSms->setContent(substr($content, 0, 1024));
        $fakeSms->setDirection($direction);
        $fakeSms->setCreatedAt(new DateTime());

        $this->_em->persist($fakeSms);
        $this->_em->flush($fakeSms);
    }

    public function findMessagesHavingIdGreaterThan(string $phoneNumber, ?string $lastMessageId): array
    {
        $builder = $this->createQueryBuilder('s')
                        ->where('s.phoneNumber = :phoneNumber')
                        ->setParameter('phoneNumber', $phoneNumber)
                        ->orderBy('s.createdAt');

        if ($lastMessageId) {
            $builder->andWhere('s.id > :lastMessageId')
                    ->setParameter('lastMessageId', $lastMessageId);
        }

        return $builder->getQuery()->getArrayResult();
    }

    public function truncate()
    {
        $this->createQueryBuilder('s')->delete()->getQuery()->execute();
    }
}

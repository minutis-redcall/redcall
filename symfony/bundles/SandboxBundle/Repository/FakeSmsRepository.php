<?php

namespace Bundles\SandboxBundle\Repository;

use App\Entity\Volunteer;
use Bundles\SandboxBundle\Entity\FakeSms;
use Doctrine\ORM\EntityRepository;

/**
 * @method FakeSms|null find($id, $lockMode = null, $lockVersion = null)
 * @method FakeSms|null findOneBy(array $criteria, array $orderBy = null)
 * @method FakeSms[]    findAll()
 * @method FakeSms[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FakeSmsRepository extends EntityRepository
{
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

    /**
     * @param string $phoneNumber
     *
     * @return array
     */
    public function findMessagesForPhoneNumber(string $phoneNumber): array
    {
        return $this->createQueryBuilder('s')
                    ->where('s.phoneNumber = :phoneNumber')
                    ->setParameter('phoneNumber', $phoneNumber)
                    ->orderBy('s.id', 'ASC')
                    ->getQuery()
                    ->getResult();
    }

    /**
     * @param Volunteer $volunteer
     * @param string    $content
     * @param string    $direction
     */
    public function save(Volunteer $volunteer, string $content, string $direction)
    {
        $fakeSms = new FakeSms();
        $fakeSms->setName(sprintf('%s %s', $volunteer->getFirstName(), $volunteer->getLastName()));
        $fakeSms->setPhoneNumber($volunteer->getPhoneNumber());
        $fakeSms->setContent(substr($content, 0, 1024));
        $fakeSms->setDirection($direction);
        $fakeSms->setCreatedAt(new \DateTime());

        $this->_em->persist($fakeSms);
        $this->_em->flush($fakeSms);
    }

    /**
     * @param string      $phoneNumber
     * @param string|null $lastMessageId
     *
     * @return array
     */
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

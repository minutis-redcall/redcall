<?php

namespace App\Repository;

use App\Entity\Structure;
use App\Entity\User;
use App\Security\Helper\Security;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Bundles\PasswordLoginBundle\Repository\AbstractUserRepository;
use Bundles\PasswordLoginBundle\Repository\UserRepositoryInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends AbstractUserRepository implements UserRepositoryInterface
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        $this->security = $security;

        parent::__construct($registry, User::class);
    }

    public function save(AbstractUser $user)
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function remove(AbstractUser $user)
    {
        $this->_em->remove($user);
        $this->_em->flush();
    }

    public function createTrustedUserQueryBuilder() : QueryBuilder
    {
        return $this->createQueryBuilder('u')
                    ->where('u.isTrusted = true');
    }

    public function findOneByExternalId(string $platform, string $externalId) : ?User
    {
        return $this
            ->createQueryBuilder('u')
            ->join('u.volunteer', 'v')
            ->where('v.platform = :platform')
            ->setParameter('platform', $platform)
            ->andWhere('v.externalId = :externalId')
            ->setParameter('externalId', $externalId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findAll()
    {
        return $this->findBy([
            'platform' => $this->security->getPlatform(),
        ]);
    }

    public function searchQueryBuilder(?string $criteria, ?bool $onlyAdmins, ?bool $onlyDevelopers) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->leftJoin('u.volunteer', 'v')
            ->leftJoin('v.phones', 'p')
            ->where(
                $qb->expr()->orX(
                    'u.username LIKE :criteria',
                    'v.externalId LIKE :criteria',
                    'v.firstName LIKE :criteria',
                    'v.lastName LIKE :criteria',
                    'v.email LIKE :criteria',
                    'p.e164 LIKE :criteria',
                    'p.national LIKE :criteria',
                    'p.international LIKE :criteria',
                    'CONCAT(v.firstName, \' \', v.lastName) LIKE :criteria',
                    'CONCAT(v.lastName, \' \', v.firstName) LIKE :criteria'
                )
            )
            ->setParameter('criteria', sprintf('%%%s%%', $criteria))
            ->andWhere('u.isRoot = true OR u.platform = :platform')
            ->setParameter('platform', $this->security->getPlatform())
            ->addOrderBy('u.registeredAt', 'DESC')
            ->addOrderBy('u.username', 'ASC');

        if ($onlyAdmins) {
            $qb->andWhere('u.isAdmin = true');
        }

        if ($onlyDevelopers) {
            $qb->andWhere('u.isDeveloper = true');
        }

        return $qb;
    }

    public function getRedCallUsersInStructure(Structure $structure) : array
    {
        return $this->createTrustedUserQueryBuilder()
                    ->join('u.structures', 's')
                    ->andWhere('s.id = :structure')
                    ->setParameter('structure', $structure)
                    ->andWhere('u.platform = :platform')
                    ->setParameter('platform', $structure->getPlatform())
                    ->getQuery()
                    ->getResult();
    }

    public function getUserCountInStructure(Structure $structure) : int
    {
        return $this->createTrustedUserQueryBuilder()
                    ->select('COUNT(u.id)')
                    ->join('u.structures', 's')
                    ->andWhere('s.id = :structure')
                    ->setParameter('structure', $structure)
                    ->andWhere('u.platform = :platform')
                    ->setParameter('platform', $structure->getPlatform())
                    ->getQuery()
                    ->getSingleScalarResult();
    }
}


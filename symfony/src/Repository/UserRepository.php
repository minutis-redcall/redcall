<?php

namespace App\Repository;

use App\Entity\Structure;
use App\Entity\User;
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
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(AbstractUser $user)
    {
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function remove(AbstractUser $user)
    {
        $this->getEntityManager()->remove($user);
        $this->getEntityManager()->flush();
    }

    public function findAll(): array
    {
        return $this->findBy([]);
    }

    public function findOneByExternalId(string $externalId) : ?User
    {
        return $this->findOneBy([
            'externalId' => $externalId,
        ]);
    }

    /**
     * Resolves the RedCall operator for a NIVOL, but only if trusted. This
     * preserves the semantics of the old Volunteer::getUser(), which silently
     * filtered out non-trusted users.
     */
    public function findOneTrustedByExternalId(?string $externalId) : ?User
    {
        if (!$externalId) {
            return null;
        }

        return $this
            ->createQueryBuilder('u')
            ->where('u.externalId = :externalId')
            ->andWhere('u.isTrusted = true')
            ->setParameter('externalId', $externalId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Batch variant of findOneTrustedByExternalId for list views: returns a
     * map [externalId => User] so callers can avoid an N+1 of per-row lookups.
     *
     * @param string[] $externalIds
     *
     * @return array<string, User>
     */
    public function findTrustedByExternalIds(array $externalIds) : array
    {
        $externalIds = array_values(array_unique(array_filter($externalIds)));
        if (!$externalIds) {
            return [];
        }

        $users = $this
            ->createQueryBuilder('u')
            ->where('u.externalId IN (:externalIds)')
            ->andWhere('u.isTrusted = true')
            ->setParameter('externalIds', $externalIds)
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($users as $user) {
            /** @var User $user */
            $map[$user->getExternalId()] = $user;
        }

        return $map;
    }

    public function findOneByUsername(string $username) : ?User
    {
        return $this->findOneBy([
            'username' => $username,
        ]);
    }

    public function searchQueryBuilder(?string $criteria, ?bool $onlyAdmins) : QueryBuilder
    {
        $qb = $this->createQueryBuilder('u');

        $qb
            ->where(
                $qb->expr()->orX(
                    'u.username LIKE :criteria',
                    'u.externalId LIKE :criteria',
                    'u.firstName LIKE :criteria',
                    'u.lastName LIKE :criteria',
                    'CONCAT(u.firstName, \' \', u.lastName) LIKE :criteria',
                    'CONCAT(u.lastName, \' \', u.firstName) LIKE :criteria'
                )
            )
            ->setParameter('criteria', sprintf('%%%s%%', $criteria))
            ->addOrderBy('u.registeredAt', 'DESC')
            ->addOrderBy('u.username', 'ASC');

        if ($onlyAdmins) {
            $qb->andWhere('u.isAdmin = true');
        }

        return $qb;
    }

    public function getRedCallUsersInStructure(Structure $structure) : array
    {
        return $this->createTrustedUserQueryBuilder()
                    ->join('u.structures', 's')
                    ->andWhere('s.id = :structure')
                    ->setParameter('structure', $structure)
                    ->getQuery()
                    ->getResult();
    }

    public function createTrustedUserQueryBuilder() : QueryBuilder
    {
        return $this->createQueryBuilder('u')
                    ->where('u.isTrusted = true');
    }

    public function findAllWithStructure(Structure $structure) : array
    {
        return $this->createQueryBuilder('u')
                    ->join('u.structures', 's')
                    ->andWhere('s.id = :structure')
                    ->setParameter('structure', $structure)
                    ->getQuery()
                    ->getResult();
    }
}


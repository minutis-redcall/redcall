<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\UserInformation;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method UserInformation|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserInformation|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserInformation[]    findAll()
 * @method UserInformation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserInformationRepository extends BaseRepository
{
    /**
     * @param RegistryInterface $registry
     */
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, UserInformation::class);
    }

    /**
     * @param UserInformation $userPreference
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function changeLocale(UserInterface $user, string $locale)
    {
        $preferences = $this->getByUser($user);
        $preferences->setLocale($locale);

        $this->_em->persist($preferences);
        $this->_em->flush($preferences);
    }

    /**
     * @param UserInterface $user
     *
     * @return UserInformation
     * @throws NonUniqueResultException
     */
    public function getByUser(UserInterface $user): UserInformation
    {
        $preferences = $this->createQueryBuilder('p')
                            ->where('p.user = :user')
                            ->setParameter('user', $user)
                            ->getQuery()
                            ->useResultCache(false)
                            ->getOneOrNullResult();

        if (!$preferences) {
            $preferences = new UserInformation();
            $preferences->setUser($user);
        }

        return $preferences;
    }

    /**
     * @param UserInterface $user
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function removeForUser(UserInterface $user)
    {
        $preferences = $this->find($user);

        if ($preferences) {
            $this->_em->remove($preferences);
            $this->_em->flush($preferences);
        }
    }

    /**
     * @param string $criteria
     *
     * @return QueryBuilder
     */
    public function searchQueryBuilder(?string $criteria): QueryBuilder
    {
        return $this->createQueryBuilder('ui')
                    ->join('ui.user', 'u')
                    ->where('u.username LIKE :criteria')
                    ->orWhere('ui.nivol LIKE :criteria')
                    ->setParameter('criteria', sprintf('%%%s%%', $criteria))
                    ->addOrderBy('u.registeredAt', 'DESC')
                    ->addOrderBy('u.username', 'ASC');
    }
}

<?php

namespace App\Repository;

use App\Entity\UserPreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method UserPreference|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserPreference|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserPreference[]    findAll()
 * @method UserPreference[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserPreferenceRepository extends ServiceEntityRepository
{
    /**
     * UserPreferenceRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserPreference::class);
    }

    /**
     * @param UserPreference $userPreference
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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
     * @return UserPreference
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getByUser(UserInterface $user): UserPreference
    {
        $preferences = $this->createQueryBuilder('p')
                            ->where('p.user = :user')
                            ->setParameter('user', $user)
                            ->getQuery()
                            ->useResultCache(false)
                            ->getOneOrNullResult();

        if (!$preferences) {
            $preferences = new UserPreference();
            $preferences->setUser($user);
        }

        return $preferences;
    }

    /**
     * @param UserInterface $user
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function removeForUser(UserInterface $user)
    {
        $preferences = $this->find($user);

        $this->_em->remove($preferences);
        $this->_em->flush($preferences);
    }
}

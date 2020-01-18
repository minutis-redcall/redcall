<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\UserInformation;
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
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, UserInformation::class);
    }

    /**
     * @param UserInformation $userPreference
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
     * @return UserInformation
     * @throws \Doctrine\ORM\NonUniqueResultException
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

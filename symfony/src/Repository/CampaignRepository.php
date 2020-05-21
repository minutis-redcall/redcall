<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Campaign;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;

/**
 * @method Campaign|null find($id, $lockMode = null, $lockVersion = null)
 * @method Campaign|null findOneBy(array $criteria, array $orderBy = null)
 * @method Campaign[]    findAll()
 * @method Campaign[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampaignRepository extends BaseRepository
{
    public function __construct(Registry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    /**
     * @param int $campaignId
     *
     * @return Campaign|null
     */
    public function findOneByIdNoCache(int $campaignId)
    {
        $this->_em->clear();

        return $this->createQueryBuilder('c')
                    ->where('c.id = :id')
                    ->setParameter('id', $campaignId)
                    ->getQuery()
                    ->useResultCache(false)
                    ->getOneOrNullResult();
    }

    /**
     * @param AbstractUser $user
     *
     * @return QueryBuilder
     */
    public function getActiveCampaignsForAdminQueryBuilder(AbstractUser $user): QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->innerJoin('c.structures', 's')
            ->innerJoin('s.users', 'u')
            ->where('u.id = :user')
            ->setParameter('user', $user)
            ->andWhere('c.active = true');
    }

    /**
     * @param AbstractUser $user
     *
     * @return QueryBuilder
     */
    public function getActiveCampaignsForUserQueryBuilder(AbstractUser $user): QueryBuilder
    {
        return $this->getActiveCampaignsForAdminQueryBuilder($user);
    }

    /**
     * @param AbstractUser $user
     *
     * @return QueryBuilder
     */
    public function getInactiveCampaignsForAdminQueryBuilder(AbstractUser $user): QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->innerJoin('c.structures', 's')
            ->innerJoin('s.users', 'u')
            ->where('u.id = :user')
            ->setParameter('user', $user)
            ->andWhere('c.active = false');
    }

    /**
     * @param AbstractUser $user
     *
     * @return QueryBuilder
     */
    public function getInactiveCampaignsForUserQueryBuilder(AbstractUser $user): QueryBuilder
    {
        return $this->getInactiveCampaignsForAdminQueryBuilder($user);
    }

    /**
     * @param Campaign $campaign
     */
    public function closeCampaign(Campaign $campaign)
    {
        $campaign->setActive(false);

        $this->save($campaign);
    }

    /**
     * @param Campaign $campaign
     */
    public function openCampaign(Campaign $campaign)
    {
        $campaign->setActive(true);

        $this->save($campaign);
    }


    /**
     * @param Campaign $campaign
     * @param string   $color
     */
    public function changeColor(Campaign $campaign, string $color)
    {
        $campaign->setType($color);

        $this->save($campaign);
    }

    /**
     * @param Campaign $campaign
     * @param string   $newName
     */
    public function changeName(Campaign $campaign, string $newName)
    {
        $campaign->setLabel($newName);

        $this->save($campaign);
    }

    /**
     * Return all active campaigns
     *
     * @return QueryBuilder
     */
    public function getActiveCampaignsQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->where('c.active = :active')
            ->setParameter('active', true);
    }

    public function getAllCampaignsQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c');
    }

    /**
     * @return int
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countAllOpenCampaigns(): int
    {
        return $this->getActiveCampaignsQueryBuilder()
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int $days
     *
     * @return array
     *
     * @throws \Exception
     */
    public function findInactiveCampaignsSince(int $days): array
    {
        return $this->getActiveCampaignsQueryBuilder()
            ->join('c.communications', 'co')
            ->andWhere('co.createdAt < :limit')
            ->setParameter('limit', (new \DateTime('now'))->sub(new \DateInterval(sprintf('P%dD', $days))))
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Campaign;
use Bundles\PasswordLoginBundle\Entity\User;
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
     * @param User $user
     *
     * @return QueryBuilder
     */
    public function getActiveCampaignsForUserQueryBuilder(User $user): QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->innerJoin('c.structures', 's')
            ->innerJoin('s.users', 'u')
            ->where('u.user = :user')
            ->setParameter('user', $user)
            ->andWhere('c.active = true');
    }

    /**
     * @param User $user
     *
     * @return QueryBuilder
     */
    public function getInactiveCampaignsForUserQueryBuilder(User $user): QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->innerJoin('c.structures', 's')
            ->innerJoin('s.users', 'u')
            ->where('u.user = :user')
            ->setParameter('user', $user)
            ->andWhere('c.active = false');
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
    public function getActiveCampaigns()
    {
        return $this->createQueryBuilder('c')
            ->where('c.active = :active')
            ->setParameter('active', true);
    }
}

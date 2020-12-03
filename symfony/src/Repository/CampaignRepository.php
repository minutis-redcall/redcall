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
                    ->disableResultCache()
                    ->getOneOrNullResult();
    }

    public function getCampaignsOpenedByMeOrMyCrew(AbstractUser $user) : QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->distinct()
            ->innerJoin('c.communications', 'co')
            ->innerJoin('co.volunteer', 'v')
            ->innerJoin('v.structures', 's')
            ->innerJoin('s.users', 'u')
            ->where('u.id = :user')
            ->setParameter('user', $user)
            ->andWhere('c.active = true');
    }

    public function getCampaignImpactingMyVolunteers(AbstractUser $user) : QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->distinct()
            ->innerJoin('c.communications', 'co')
            ->innerJoin('co.messages', 'm')
            ->innerJoin('m.volunteer', 'v')
            ->innerJoin('v.structures', 's')
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
    public function getInactiveCampaignsForUserQueryBuilder(AbstractUser $user) : QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->distinct()
            ->innerJoin('c.structures', 's')
            ->innerJoin('s.users', 'u')
            ->where('u.id = :user')
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

    public function changeName(Campaign $campaign, string $newName)
    {
        $campaign->setLabel($newName);

        $this->save($campaign);
    }

    public function changeNotes(Campaign $campaign, string $notes)
    {
        $campaign->setNotes($notes);
        $campaign->setNotesUpdatedAt(new \DateTime());

        $this->save($campaign);
    }

    /**
     * Return all active campaigns
     *
     * @return QueryBuilder
     */
    public function getActiveCampaignsQueryBuilder() : QueryBuilder
    {
        return $this->createQueryBuilder('c')
                    ->where('c.active = :active')
                    ->setParameter('active', true);
    }

    public function getAllCampaignsQueryBuilder() : QueryBuilder
    {
        return $this->createQueryBuilder('c');
    }

    /**
     * @return int
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\NoResultException
     */
    public function countAllOpenCampaigns() : int
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
    public function findInactiveCampaignsSince(int $days) : array
    {
        return $this->getActiveCampaignsQueryBuilder()
                    ->join('c.communications', 'co')
                    ->andWhere('co.createdAt < :limit')
                    ->setParameter('limit', (new \DateTime('now'))->sub(new \DateInterval(sprintf('P%dD', $days))))
                    ->getQuery()
                    ->getResult();
    }

    public function getNoteUpdateTimestamp(int $campaignId) : int
    {
        $row = $this->createQueryBuilder('c')
                    ->select('c.notesUpdatedAt')
                    ->where('c.id = :campaignId')
                    ->setParameter('campaignId', $campaignId)
                    ->getQuery()
                    ->disableResultCache()
                    ->getOneOrNullResult();

        if ($row && $row['notesUpdatedAt'] && $row['notesUpdatedAt'] instanceof \DateTime) {
            return $row['notesUpdatedAt']->getTimestamp();
        }

        return 0;
    }

    public function countNumberOfMessagesSent(int $campaignId) : int
    {
        return $this->createQueryBuilder('c')
                    ->select('COUNT(m.id)')
                    ->join('c.communications', 'co')
                    ->join('co.messages', 'm')
                    ->where('c.id = :campaignId')
                    ->setParameter('campaignId', $campaignId)
                    ->andWhere('m.sent = 1')
                    ->getQuery()
                    ->disableResultCache()
                    ->getSingleScalarResult();
    }

    public function countNumberOfAnswersReceived(int $campaignId) : int
    {
        return $this->createQueryBuilder('c')
                    ->select('COUNT(a.id)')
                    ->join('c.communications', 'co')
                    ->join('co.messages', 'm')
                    ->join('m.answers', 'a')
                    ->where('c.id = :campaignId')
                    ->setParameter('campaignId', $campaignId)
                    ->getQuery()
                    ->disableResultCache()
                    ->getSingleScalarResult();
    }

    public function countNumberOfGeoLocationReceived(int $campaignId) : int
    {
        return $this->createQueryBuilder('c')
                    ->select('COUNT(g.id)')
                    ->join('c.communications', 'co')
                    ->join('co.messages', 'm')
                    ->join('m.geoLocation', 'g')
                    ->where('c.id = :campaignId')
                    ->setParameter('campaignId', $campaignId)
                    ->getQuery()
                    ->disableResultCache()
                    ->getSingleScalarResult();
    }

    public function getLastGeoLocationUpdated(int $campaignId) : int
    {
        $row = $this->createQueryBuilder('c')
                    ->select('g.datetime')
                    ->join('c.communications', 'co')
                    ->join('co.messages', 'm')
                    ->join('m.geoLocation', 'g')
                    ->where('c.id = :campaignId')
                    ->setParameter('campaignId', $campaignId)
                    ->orderBy('g.datetime', 'DESC')
                    ->setMaxResults(1)
                    ->getQuery()
                    ->disableResultCache()
                    ->getOneOrNullResult();

        return $row && $row['datetime'] ? $row['datetime']->getTimestamp() : 0;
    }
}

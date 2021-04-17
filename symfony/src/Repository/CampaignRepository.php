<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Campaign;
use App\Security\Helper\Security;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Campaign|null find($id, $lockMode = null, $lockVersion = null)
 * @method Campaign|null findOneBy(array $criteria, array $orderBy = null)
 * @method Campaign[]    findAll()
 * @method Campaign[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CampaignRepository extends BaseRepository
{
    /**
     * @var Security
     */
    private $security;

    public function __construct(Security $security, ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);

        $this->security = $security;
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
            ->andWhere('s.enabled = true')
            ->andWhere('c.platform = :platform')
            ->setParameter('platform', $this->security->getPlatform())
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
            ->andWhere('s.enabled = true')
            ->andWhere('c.platform = :platform')
            ->setParameter('platform', $this->security->getPlatform())
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
            ->innerJoin('c.communications', 'co')
            ->innerJoin('co.messages', 'm')
            ->innerJoin('m.volunteer', 'v')
            ->innerJoin('v.structures', 's')
            ->innerJoin('s.users', 'u')
            ->where('u.id = :user')
            ->setParameter('user', $user)
            ->andWhere('s.enabled = true')
            ->andWhere('c.platform = :platform')
            ->setParameter('platform', $this->security->getPlatform())
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
        $campaign->setExpiresAt(
            (new \DateTime())->add(new \DateInterval('P7D'))
        );

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

    public function getAllCampaignsQueryBuilder(string $platform) : QueryBuilder
    {
        return $this->createQueryBuilder('c')
                    ->where('c.platform = :platform')
                    ->setParameter('platform', $platform);
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

    public function closeExpiredCampaigns()
    {
        $this->getActiveCampaignsQueryBuilder()
             ->update(Campaign::class, 'c')
             ->set('c.active', 0)
             ->andWhere('c.expiresAt < :now')
             ->setParameter('now', (new \DateTime())->format('Y-m-d H:i:s'))
             ->getQuery()
             ->execute();
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

    public function getCampaignAudience(Campaign $campaign) : array
    {
        return $this->createQueryBuilder('c')
                    ->select('s.id as structure_id, s.name as structure_name, COUNT(DISTINCT v) AS volunteer_count')
                    ->join('c.communications', 'co')
                    ->join('co.messages', 'm')
                    ->join('m.volunteer', 'v')
                    ->join('v.structures', 's')
                    ->where('c.id = :campaign_id')
                    ->setParameter('campaign_id', $campaign->getId())
                    ->andWhere('s.enabled = true')
                    ->andWhere('s.platform = :platform')
                    ->setParameter('platform', $this->security->getPlatform())
                    ->orderBy('volunteer_count', 'DESC')
                    ->groupBy('s.id')
                    ->getQuery()
                    ->getArrayResult();
    }
}

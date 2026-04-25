<?php

namespace App\Repository;

use App\Base\BaseRepository;
use App\Entity\Campaign;
use App\Entity\Message;
use Bundles\PasswordLoginBundle\Entity\AbstractUser;
use Doctrine\ORM\Query;
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
    const CODE_SIZE = 8;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Campaign::class);
    }

    /**
     * Fetches a campaign with all related data (communications, choices,
     * messages, answers, volunteers) using HINT_REFRESH to overwrite the
     * identity map without $em->clear().
     *
     * Uses two queries to avoid Cartesian product explosion:
     * 1. Campaign + Communications + Choices (small)
     * 2. Messages + Answers + Answer-choices + Volunteers (populates identity map)
     */
    public function findCampaignWithFreshData(int $campaignId) : ?Campaign
    {
        // Query 1: Campaign + Communications + Choices
        $campaign = $this->createQueryBuilder('c')
                        ->addSelect('co', 'ch')
                        ->leftJoin('c.communications', 'co')
                        ->leftJoin('co.choices', 'ch')
                        ->where('c.id = :id')
                        ->setParameter('id', $campaignId)
                        ->getQuery()
                        ->disableResultCache()
                        ->setHint(Query::HINT_REFRESH, true)
                        ->getOneOrNullResult();

        if (!$campaign) {
            return null;
        }

        // Query 2: Pre-load all messages with their answers, answer-choices, and volunteers
        // This populates the identity map so getCampaignStatus() won't trigger lazy-loading
        $communicationIds = [];
        foreach ($campaign->getCommunications() as $communication) {
            $communicationIds[] = $communication->getId();
        }

        if ($communicationIds) {
            $this->_em->createQueryBuilder()
                ->select('m', 'a', 'ach', 'v')
                ->from(Message::class, 'm')
                ->leftJoin('m.answers', 'a')
                ->leftJoin('a.choices', 'ach')
                ->leftJoin('m.volunteer', 'v')
                ->where('m.communication IN (:communicationIds)')
                ->setParameter('communicationIds', $communicationIds)
                ->getQuery()
                ->disableResultCache()
                ->setHint(Query::HINT_REFRESH, true)
                ->getResult();
        }

        return $campaign;
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
            ->andWhere('c.active = true')
            ->andWhere('c.id NOT IN (
                SELECT c2.id FROM App\Entity\Campaign c2
                JOIN c2.communications co2
                JOIN co2.volunteer v2
                JOIN v2.structures s2
                JOIN s2.users u2
                WHERE u2.id = :user AND s2.enabled = true AND c2.active = true
            )');
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
        $campaign->touchActivity();

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

    public function getHashData(int $campaignId) : ?array
    {
        return $this->createQueryBuilder('c')
                    ->select('c.lastActivityAt, c.notesUpdatedAt')
                    ->where('c.id = :id')
                    ->setParameter('id', $campaignId)
                    ->getQuery()
                    ->disableResultCache()
                    ->getOneOrNullResult();
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
                    ->orderBy('volunteer_count', 'DESC')
                    ->groupBy('s.id')
                    ->getQuery()
                    ->getArrayResult();
    }
}

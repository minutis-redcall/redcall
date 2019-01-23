<?php

namespace App\Repository;

use App\Entity\Answer;
use App\Entity\Campaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Answer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Answer|null findOneBy(array $criteria, array $orderBy = null)
 * @method Answer[]    findAll()
 * @method Answer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnswerRepository extends ServiceEntityRepository
{
    /**
     * AnswerRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Answer::class);
    }

    /**
     * @param Campaign $campaign
     *
     * @return int|null
     */
    public function getLastCampaignUpdateTimestamp(Campaign $campaign): ?int
    {
        $lastAnswer = $this->createQueryBuilder('a')
                           ->join('a.message', 'm')
                           ->join('m.communication', 'co')
                           ->join('co.campaign', 'ca')
                           ->where('ca.id = :campaignId')
                           ->setParameter('campaignId', $campaign->getId())
                           ->orderBy('a.updatedAt', 'DESC')
                           ->setMaxResults(1)
                           ->getQuery()
                           ->useResultCache(false)
                           ->getOneOrNullResult();

        if ($lastAnswer) {
            $this->_em->detach($lastAnswer);

            /* @var \App\Entity\Answer $lastAnswer */
            return $lastAnswer->getUpdatedAt()->getTimestamp();
        }

        return null;
    }
}

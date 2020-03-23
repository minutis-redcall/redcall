<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Repository\CostRepository;
use App\Repository\MessageRepository;

class StatisticsManager
{

    /**
     * @var MessageRepository
     */
    private $messageRepository;
    /**
     * @var CostRepository
     */
    private $costRepository;

    public function __construct(MessageRepository $messageRepository, CostRepository $costRepository)
    {
        $this->messageRepository = $messageRepository;
        $this->costRepository = $costRepository;
    }

    /**
     * Returns all statistics for the dashboard
     * If structure is filled, all stats returned will be filter by the structure. Otherwise the statistics will be global
     *
     * @param \DateTime $from
     * @param \DateTime $to
     * @param Structure|null $structure
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDashboardStatistics(\DateTime $from, \DateTime $to, Structure $structure = null)
    {
        $statistics = [];

        $countMessages = $this->messageRepository->getNumberOfSentMessagesByKind($from, $to);

        $totalCount = 0;
        foreach ($countMessages as $countByType) {
            $statistics['messagesSent']['types'][$countByType['type']] = $countByType['count'];
            $totalCount += $countByType['count'];
        }
        $statistics['messagesSent']['totalCount'] = $totalCount;


        $statistics['triggeredVolounteers'] = $this->messageRepository->getNumberOfTriggeredVolounteers($from, $to)['volounteers'];

        $statistics['answersReceived'] = $this->messageRepository->getNumberOfAnswersReceived($from, $to)['answers'];

        $statistics['costs'] = $this->costRepository->getSumOfCost($from, $to);

        return $statistics;
    }

}
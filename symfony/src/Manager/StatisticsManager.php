<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Repository\CampaignRepository;
use App\Repository\CostRepository;
use App\Repository\MessageRepository;
use App\Repository\VolunteerRepository;

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
    /**
     * @var CampaignRepository
     */
    private $campaignRepository;
    /**
     * @var VolunteerRepository
     */
    private $volunteerRepository;

    public function __construct(MessageRepository $messageRepository, CostRepository $costRepository, CampaignRepository $campaignRepository, VolunteerRepository $volunteerRepository)
    {
        $this->messageRepository = $messageRepository;
        $this->costRepository = $costRepository;
        $this->campaignRepository = $campaignRepository;
        $this->volunteerRepository = $volunteerRepository;
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

        //Campaign section
        $openCampagins = $this->campaignRepository->getActiveCampaigns();
        $statistics['openCampaigns'] = count($openCampagins->select('c.id')->getQuery()->getResult());

        //Messages section
        $countMessages = $this->messageRepository->getNumberOfSentMessagesByKind($from, $to);

        $totalCount = 0;
        foreach ($countMessages as $countByType) {
            $statistics['messagesSent']['types'][$countByType['type']] = $countByType['count'];
            $totalCount += $countByType['count'];
        }
        $statistics['messagesSent']['totalCount'] = $totalCount;


        $statistics['triggeredVolounteers'] = $this->messageRepository->getNumberOfTriggeredVolounteers($from, $to)['volounteers'];

        $statistics['answersReceived'] = $this->messageRepository->getNumberOfAnswersReceived($from, $to)['answers'];

        //Costs section
        $costsByDirection = $this->costRepository->getSumOfCost($from, $to);
        if (!empty($costsByDirection)) {
            $totalCost = 0;

            foreach ($costsByDirection as $cost) {
                $totalCost += abs($cost['cost']);
                $statistics['costs']['types'][$cost['direction']] = abs($cost['cost']);
            }
            $statistics['costs']['total'] = $totalCost;
            $statistics['costs']['currency'] = $costsByDirection[0]['currency'];
        }

        //Volunteers Section
        $volunteersStats = $this->volunteerRepository->getEmailAndPhoneNumberMissings();
        $array = [
            'total' => [
                'number' => $volunteersStats['one_is_null'],
                'percent' => $volunteersStats['one_is_null'] / $volunteersStats['total'] * 100
            ],
            'email' => [
                'number' => $volunteersStats['email_null'],
                'percent' => $volunteersStats['email_null'] / $volunteersStats['total'] * 100
            ],
            'phone' => [
                'number' => $volunteersStats['phone_null'],
                'percent' => $volunteersStats['phone_null'] / $volunteersStats['total'] * 100
            ],
            'both' => [
                'number' => $volunteersStats['both_null'],
                'percent' => $volunteersStats['both_null'] / $volunteersStats['total'] * 100
            ]
        ];

        $statistics['volunteers'] = $array;
        return $statistics;
    }

}
<?php

namespace App\Manager;

use App\Entity\Structure;
use App\Repository\StatisticsRepository;

class StatisticsManager
{
    /**
     * @var StatisticsRepository
     */
    private $statisticsRepository;

    /**
     * @var CampaignManager
     */
    private $campaignManager;

    /**
     * @param StatisticsRepository $statisticsRepository
     * @param CampaignManager      $campaignManager
     */
    public function __construct(StatisticsRepository $statisticsRepository, CampaignManager $campaignManager)
    {
        $this->statisticsRepository = $statisticsRepository;
        $this->campaignManager      = $campaignManager;
    }

    /**
     * Returns all statistics for the dashboard
     * If structure is filled, all stats returned will be filter by the structure. Otherwise the statistics will be
     * global
     *
     * @param \DateTime      $from
     * @param \DateTime      $to
     * @param Structure|null $structure
     *
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDashboardStatistics(\DateTime $from, \DateTime $to, Structure $structure = null)
    {
        $statistics = [];

        //Campaign section
        $statistics['openCampaigns']   = $this->campaignManager->countAllOpenCampaigns();
        $statistics['campaignsPeriod'] = $this->statisticsRepository->getNumberOfCampaigns($from, $to);

        //Messages section
        $countMessages = $this->statisticsRepository->getNumberOfSentMessagesByKind($from, $to);

        $totalCount = 0;
        foreach ($countMessages as $countByType) {
            $statistics['messagesSent']['types'][$countByType['type']] = $countByType['count'];
            $totalCount                                                += $countByType['count'];
        }
        $statistics['messagesSent']['totalCount'] = $totalCount;

        $statistics['triggeredVolounteers'] = $this->statisticsRepository->getNumberOfTriggeredVolounteers($from, $to)['volounteers'];

        $statistics['answersReceived'] = $this->statisticsRepository->getNumberOfAnswersReceived($from, $to)['answers'];

        //Costs section
        $costsByDirection = $this->statisticsRepository->getSumOfCost($from, $to);
        if (!empty($costsByDirection)) {
            // TODO Wrong, currencies are mixed
            $totalCost = 0;
            foreach ($costsByDirection as $cost) {
                $totalCost += abs($cost['cost']);
                if (!isset($statistics['costs']['types'][$cost['direction']])) {
                    $statistics['costs']['types'][$cost['direction']] = 0;
                }
                $statistics['costs']['types'][$cost['direction']] += abs($cost['cost']);
            }
            $statistics['costs']['total']    = $totalCost;
            $statistics['costs']['currency'] = $costsByDirection[0]['currency'];
        }

        //Volunteers Section
        $volunteersStats          = $this->statisticsRepository->getEmailAndPhoneNumberMissings();
        $array                    = [
            'total' => [
                'number'  => $volunteersStats['one_is_null'],
                'percent' => $volunteersStats['one_is_null'] / $volunteersStats['total'] * 100,
            ],
            'email' => [
                'number'  => $volunteersStats['email_null'],
                'percent' => $volunteersStats['email_null'] / $volunteersStats['total'] * 100,
            ],
            'phone' => [
                'number'  => $volunteersStats['phone_null'],
                'percent' => $volunteersStats['phone_null'] / $volunteersStats['total'] * 100,
            ],
            'both'  => [
                'number'  => $volunteersStats['both_null'],
                'percent' => $volunteersStats['both_null'] / $volunteersStats['total'] * 100,
            ],
        ];
        $statistics['volunteers'] = $array;

        //Pegass update Section
        $statistics['pegassUpdates']['structures'] = $this->statisticsRepository->getStructurePegassUpdate();
        $statistics['pegassUpdates']['volunteers'] = $this->statisticsRepository->getVolunteerPegassUpdate();

        return $statistics;
    }
}
<?php

namespace App\Manager;

use App\Entity\Campaign;
use App\Repository\AnswerRepository;

class AnswerManager
{
    /**
     * @var AnswerRepository
     */
    private $answerRepository;

    /**
     * @param AnswerRepository $answerRepository
     */
    public function __construct(AnswerRepository $answerRepository)
    {
        $this->answerRepository = $answerRepository;
    }

    /**
     * @param Campaign $campaign
     *
     * @return int|null
     */
    public function getLastCampaignUpdateTimestamp(Campaign $campaign) : ?int
    {
        return $this->answerRepository->getLastCampaignUpdateTimestamp($campaign);
    }
}
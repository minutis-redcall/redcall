<?php

namespace App\Manager;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Message;
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
    public function getLastCampaignUpdateTimestamp(Campaign $campaign): ?int
    {
        return $this->answerRepository->getLastCampaignUpdateTimestamp($campaign);
    }

    /**
     * @param Message $message
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function clearAnswers(Message $message)
    {
        $this->answerRepository->clearAnswers($message);
    }

    /**
     * @param Message $message
     * @param array   $choices
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function clearChoices(Message $message, array $choices)
    {
        $this->answerRepository->clearChoices($message, $choices);
    }

    /**
     * @param Answer $answer
     */
    public function save(Answer $answer)
    {
        $this->answerRepository->save($answer);
    }
}
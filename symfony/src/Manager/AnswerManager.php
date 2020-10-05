<?php

namespace App\Manager;

use App\Entity\Answer;
use App\Entity\Campaign;
use App\Entity\Message;
use App\Repository\AnswerRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\QueryBuilder;

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
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function clearAnswers(Message $message)
    {
        $this->answerRepository->clearAnswers($message);
    }

    /**
     * @param Message $message
     * @param array   $choices
     *
     * @throws ORMException
     * @throws OptimisticLockException
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

    public function getSearchQueryBuilder(string $criteria) : QueryBuilder
    {
        return $this->answerRepository->getSearchQueryBuilder($criteria);
    }
}
<?php

namespace App\Manager;

use App\Entity\PrefilledAnswers;
use App\Repository\PrefilledAnswersRepository;
use Doctrine\ORM\QueryBuilder;

class PrefilledAnswersManager
{
    /**
     * @var PrefilledAnswersRepository
     */
    private $prefilledAnswersRepository;

    /**
     * @param PrefilledAnswersRepository $prefilledAnswersRepository
     */
    public function __construct(PrefilledAnswersRepository $prefilledAnswersRepository)
    {
        $this->prefilledAnswersRepository = $prefilledAnswersRepository;
    }

    /**
     * @return PrefilledAnswers[]
     */
    public function findAll(): array
    {
        return $this->prefilledAnswersRepository->findAll();
    }

    /**
     * Return all prefilled answers for a specific structure
     * @param \App\Entity\Structure $structure
     *
     * @return QueryBuilder
     */
    public function getPrefilledAnswersByStructure(\App\Entity\Structure $structure)
    {
        return $this->prefilledAnswersRepository->getPrefilledAnswersByStructure($structure);
    }


}
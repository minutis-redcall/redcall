<?php

namespace App\Manager;

use App\Entity\PrefilledAnswers;
use App\Repository\PrefilledAnswersRepository;

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


}
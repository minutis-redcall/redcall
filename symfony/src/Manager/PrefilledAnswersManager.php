<?php

namespace App\Manager;

use App\Entity\PrefilledAnswers;
use App\Entity\UserInformation;
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

    /**
     * Return prefilled answers by Id
     * @param $id
     * @return PrefilledAnswers|null
     */
    public function findById($id)
    {
        return $this->prefilledAnswersRepository->find($id);
    }

    /**
     * Return all prefilled answers for a given userInformation AND all global (no structure relations).
     */
    public function findByUserForStructureAndGlobal(UserInformation $userInformation)
    {
        return $this->prefilledAnswersRepository->findByUserForStructureAndGlobal($userInformation);
    }

    public function save(PrefilledAnswers $prefilledAnswers)
    {
        $this->prefilledAnswersRepository->save($prefilledAnswers);
    }

    public function remove(PrefilledAnswers $prefilledAnswers)
    {
        $this->prefilledAnswersRepository->remove($prefilledAnswers);
    }

    /**
     * Return all prefilled answers that are not related to any structure
     *
     * @return QueryBuilder
     */
    public function getGlobalPrefilledAnswers()
    {
        return $this->prefilledAnswersRepository->getGlobalPrefilledAnswers();
    }

}
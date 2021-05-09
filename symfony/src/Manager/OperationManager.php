<?php

namespace App\Manager;

use App\Entity\Campaign as CampaignEntity;
use App\Entity\Communication;
use App\Entity\Operation;
use App\Form\Model\Campaign as CampaignModel;
use App\Repository\OperationRepository;
use App\Services\Minutis;
use Psr\Log\LoggerInterface;

class OperationManager
{
    /**
     * @var OperationRepository
     */
    private $operationRepository;

    /**
     * @var Minutis
     */
    private $minutis;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(OperationRepository $operationRepository, Minutis $minutis, LoggerInterface $logger)
    {
        $this->operationRepository = $operationRepository;
        $this->minutis             = $minutis;
        $this->logger              = $logger;
    }

    public function createOperation(CampaignModel $campaignModel,
        CampaignEntity $campaignEntity,
        Communication $communication)
    {
        $operationModel = $campaignModel->operation;

        $ownerExternalId = $operationModel->ownerExternalId;
        $owner           = $this->minutis->searchForVolunteer($ownerExternalId);

        if (!$owner) {
            return;
        }

        $email = $owner['attributes']['mail']['value'] ?? null;
        if (!$email) {
            $this->logger->warning(sprintf('Cannot set "%s" as minutis operation owner because (s)he has no email', $ownerExternalId));

            return;
        }

        $id = $this->minutis->createOperation($operationModel->structure, $operationModel->name, $email);

        $operationEntity = new Operation();
        $operationEntity->setName($campaignModel->operation->name);
        $operationEntity->setCampaign($campaignEntity);
        $operationEntity->setOperationExternalId($id->getId());
        $operationEntity->setOperationExternalPublicId($id->getPublicId());

        foreach ($operationModel->choices as $choice) {
            $operationEntity->addChoice($communication->getChoiceByLabel($choice));
        }

        $this->operationRepository->save($operationEntity);
    }

    public function bindOperation(CampaignModel $campaignModel, CampaignEntity $campaignEntity)
    {

    }
}
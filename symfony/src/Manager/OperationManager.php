<?php

namespace App\Manager;

use App\Entity\Campaign;
use App\Entity\Campaign as CampaignEntity;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Operation;
use App\Entity\Structure;
use App\Form\Model\Campaign as CampaignModel;
use App\Provider\Minutis\MinutisProvider;
use App\Repository\OperationRepository;
use Psr\Log\LoggerInterface;

class OperationManager
{
    /**
     * @var OperationRepository
     */
    private $operationRepository;

    /**
     * @var MinutisProvider
     */
    private $minutis;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(OperationRepository $operationRepository, MinutisProvider $minutis, LoggerInterface $logger)
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

        $id = $this->minutis->createOperation($operationModel->structureExternalId, $operationModel->name, $email);

        $this->saveCampaignOperation($campaignModel, $campaignEntity, $communication, $id);
    }

    public function bindOperation(CampaignModel $campaignModel,
        CampaignEntity $campaignEntity,
        Communication $communication)
    {
        $operationExternalId = $campaignModel->operation->operationExternalId;

        $this->saveCampaignOperation($campaignModel, $campaignEntity, $communication, $operationExternalId);
    }

    public function isOperationExisting(int $operationExternalId) : bool
    {
        return $this->minutis->isOperationExisting($operationExternalId);
    }

    public function listOperations(Structure $structure) : array
    {
        $operations = $this->minutis->searchForOperations($structure->getExternalId());

        uksort($operations, function (int $a, int $b) {
            return $b <=> $a;
        });

        return $operations;
    }

    public function addResourceToOperation(Message $message)
    {
        $resourceExternalId = $this->minutis->addResourceToOperation(
            $message->getCommunication()->getCampaign()->getOperation()->getOperationExternalId(),
            $message->getVolunteer()->getExternalId()
        );

        $message->setResourceExternalId($resourceExternalId);
    }

    public function removeResourceFromOperation(Message $message)
    {
        $this->minutis->removeResourceFromOperation(
            $message->getCommunication()->getCampaign()->getOperation()->getOperationExternalId(),
            $message->getResourceExternalId()
        );
    }

    private function saveCampaignOperation(CampaignModel $campaignModel,
        CampaignEntity $campaignEntity,
        Communication $communication,
        int $id)
    {
        $operationEntity = new Operation();
        $operationEntity->setCampaign($campaignEntity);
        $operationEntity->setOperationExternalId($id);

//        $operationModel = $campaignModel->operation;
//        foreach ($operationModel->choices as $choice) {
//            $operationEntity->addChoice($communication->getChoiceByLabel($choice));
//        }

        $this->operationRepository->save($operationEntity);
    }
}
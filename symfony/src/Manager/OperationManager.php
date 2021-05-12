<?php

namespace App\Manager;

use App\Entity\Campaign;
use App\Entity\Campaign as CampaignEntity;
use App\Entity\Communication;
use App\Entity\Message;
use App\Entity\Operation;
use App\Entity\Structure;
use App\Form\Model\BaseTrigger;
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

    public function createOperation(CampaignModel $campaignModel, CampaignEntity $campaignEntity)
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

        $this->saveCampaignOperation($campaignEntity, $id);
    }

    public function bindOperation(CampaignModel $campaignModel, CampaignEntity $campaignEntity)
    {
        $operationExternalId = $campaignModel->operation->operationExternalId;

        $this->saveCampaignOperation($campaignEntity, $operationExternalId);
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

        $message->setResourceExternalId(null);
    }

    public function addChoicesToOperation(Communication $communication, BaseTrigger $trigger)
    {
        $operation = $communication->getCampaign()->getOperation();

        foreach ($trigger->getOperationAnswers() as $choice) {
            $operation->addChoice($communication->getChoiceByLabel($choice));
        }

        $this->operationRepository->save($operation);
    }

    private function saveCampaignOperation(CampaignEntity $campaignEntity, int $id)
    {
        $operationEntity = new Operation();
        $operationEntity->setCampaign($campaignEntity);
        $operationEntity->setOperationExternalId($id);

        $this->operationRepository->save($operationEntity);
    }
}
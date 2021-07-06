<?php

namespace Bundles\SandboxBundle\Provider;

use App\Enum\Platform;
use App\Manager\VolunteerManager;
use App\Provider\Minutis\MinutisProvider;
use Bundles\SandboxBundle\Manager\AnonymizeManager;
use Bundles\SandboxBundle\Manager\FakeOperationManager;
use Bundles\SandboxBundle\Manager\FakeOperationResourceManager;

class FakeMinutisProvider implements MinutisProvider
{
    /**
     * @var FakeOperationManager
     */
    private $operationManager;

    /**
     * @var FakeOperationResourceManager
     */
    private $operationResourceManager;

    /**
     * @var VolunteerManager
     */
    private $volunteerManager;

    public function __construct(FakeOperationManager $operationManager,
        FakeOperationResourceManager $operationResourceManager,
        VolunteerManager $volunteerManager)
    {
        $this->operationManager         = $operationManager;
        $this->operationResourceManager = $operationResourceManager;
        $this->volunteerManager         = $volunteerManager;
    }

    static public function getOperationUrl(int $operationExternalId) : string
    {
        return sprintf('%s/sandbox/fake-minutis/%d', getenv('WEBSITE_URL'), $operationExternalId);
    }

    public function searchForOperations(string $structureExternalId, string $criteria = null) : array
    {
        return $this->operationManager->search($structureExternalId, $criteria);
    }

    public function isOperationExisting(int $operationExternalId) : bool
    {
        return $this->operationManager->exists($operationExternalId);
    }

    public function getOperation(int $operationExternalId) : array
    {
        $operation = $this->operationManager->get($operationExternalId);

        if (!$operation) {
            throw new \RuntimeException('Operation not found');
        }

        return [
            'id'               => $operation->getId(),
            'owner'            => $operation->getOwnerEmail(),
            'nom'              => $operation->getName(),
            'parentExternalId' => sprintf('red_cross_france_leaf_%d', $operation->getStructureExternalId()),
        ];
    }

    public function searchForVolunteer(string $volunteerExternalId) : ?array
    {
        $volunteer = $this->volunteerManager->findOneByExternalId(Platform::FR, $volunteerExternalId);
        if ($volunteer) {
            $email = $volunteer->getEmail();
        } else {
            $email = AnonymizeManager::generateEmail(
                strtolower(AnonymizeManager::generateFirstname()),
                strtolower(AnonymizeManager::generateLastname())
            );
        }

        return [
            'attributes' => [
                'mail' => [
                    'value' => $email,
                ],
            ],
        ];
    }

    public function createOperation(string $structureExternalId, string $name, string $ownerEmail) : int
    {
        return $this->operationManager->create($structureExternalId, $name, $ownerEmail);
    }

    public function addResourceToOperation(int $externalOperationId, string $volunteerExternalId) : ?int
    {
        $operation = $this->operationManager->get($externalOperationId);

        if (null === $operation) {
            throw new \RuntimeException(sprintf('External operation #%d does not exist', $externalOperationId));
        }

        $resource = $this->operationResourceManager->create($operation, $volunteerExternalId);

        $operation->addResource($resource);

        $this->operationManager->save($operation);

        return $resource->getId();
    }

    public function removeResourceFromOperation(int $externalOperationId, int $resourceExternalId)
    {
        $operation = $this->operationManager->get($externalOperationId);

        if (null === $operation) {
            throw new \RuntimeException(sprintf('External operation #%d does not exist', $externalOperationId));
        }

        $resource = $this->operationResourceManager->find($resourceExternalId);

        if ($resource) {
            $operation->removeResource($resource);
            $this->operationResourceManager->remove($resource);
            $this->operationManager->save($operation);
        }
    }
}
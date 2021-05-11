<?php

namespace Bundles\SandboxBundle\Provider;

use App\Provider\Minutis\MinutisProvider;
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

    public function __construct(FakeOperationManager $operationManager,
        FakeOperationResourceManager $operationResourceManager)
    {
        $this->operationManager         = $operationManager;
        $this->operationResourceManager = $operationResourceManager;
    }


    static public function getOperationUrl(int $operationExternalId): string
    {
        return sprintf('%s/fake-minutis', getenv('WEBSITE_URL'));
    }

    public function searchForOperations(string $structureExternalId, string $criteria = null): array
    {
        return $this->operationManager->search($structureExternalId, $criteria);
    }

    public function isOperationExisting(int $operationExternalId): bool
    {
        return $this->operationManager->exists($operationExternalId);
    }

    public function createOperation(string $structureExternalId, string $name, string $ownerEmail): int
    {
        $this->operationManager->create($structureExternalId, $name, $ownerEmail);
    }

    public function addResourceToOperation(int $externalOperationId, string $volunteerExternalId)
    {
        $operation = $this->operationManager->find($externalOperationId);

        if (null === $operation) {
            throw new \RuntimeException(sprintf('External operation #%d does not exist', $externalOperationId));
        }

        $resource = $this->operationResourceManager->create($operation, $volunteerExternalId);

        $operation->addResource($resource);

        $this->operationManager->save($operation);
    }

}
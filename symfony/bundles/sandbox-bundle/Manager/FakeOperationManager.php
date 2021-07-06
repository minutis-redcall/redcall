<?php

namespace Bundles\SandboxBundle\Manager;

use Bundles\SandboxBundle\Entity\FakeOperation;
use Bundles\SandboxBundle\Repository\FakeOperationRepository;

class FakeOperationManager
{
    /**
     * @var FakeOperationRepository
     */
    private $operationRepository;

    public function __construct(FakeOperationRepository $operationRepository)
    {
        $this->operationRepository = $operationRepository;
    }

    /**
     * @return FakeOperation[]
     */
    public function all() : array
    {
        return $this->operationRepository->findAll();
    }

    public function clear()
    {
        $this->operationRepository->truncate();
    }

    public function search(string $structureExternalId, string $criteria = null) : array
    {
        return array_map(function (FakeOperation $operation) {
            return [
                'id'   => $operation->getId(),
                'name' => $operation->getName(),
            ];
        }, $this->operationRepository->search($structureExternalId, $criteria));
    }

    public function exists(int $operationExternalId) : bool
    {
        return null !== $this->operationRepository->find($operationExternalId);
    }

    public function create(string $structureExternalId, string $name, string $ownerEmail) : int
    {
        $operation = new FakeOperation();
        $operation->setStructureExternalId($structureExternalId);
        $operation->setName($name);
        $operation->setOwnerEmail($ownerEmail);

        $this->operationRepository->save($operation);

        return $operation->getId();
    }

    public function get(int $operationExternalId) : ?FakeOperation
    {
        return $this->operationRepository->find($operationExternalId);
    }

    public function save(FakeOperation $operation)
    {
        $this->operationRepository->save($operation);
    }
}
<?php

namespace Bundles\SandboxBundle\Manager;

use Bundles\SandboxBundle\Entity\FakeOperation;
use Bundles\SandboxBundle\Entity\FakeOperationResource;
use Bundles\SandboxBundle\Repository\FakeOperationResourceRepository;

class FakeOperationResourceManager
{
    /**
     * @var FakeOperationResourceRepository
     */
    private $operationResourceRepository;

    public function __construct(FakeOperationResourceRepository $operationResourceRepository)
    {
        $this->operationResourceRepository = $operationResourceRepository;
    }

    public function clear()
    {
        $this->operationResourceRepository->clear();
    }

    public function create(FakeOperation $operation, string $volunteerExternalId) : FakeOperationResource
    {
        $resource = new FakeOperationResource();
        $resource->setOperation($operation);
        $resource->setVolunteerExternalId($volunteerExternalId);

        $this->operationResourceRepository->save($resource);

        return $resource;
    }

    public function remove(FakeOperationResource $resource)
    {
        $this->operationResourceRepository->remove($resource);
    }
}

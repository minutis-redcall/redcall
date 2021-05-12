<?php

namespace App\Provider\Minutis;

interface MinutisProvider
{
    static public function getOperationUrl(int $operationExternalId) : string;
    public function searchForOperations(string $structureExternalId, string $criteria = null) : array;
    public function isOperationExisting(int $operationExternalId) : bool;
    public function searchForVolunteer(string $volunteerExternalId) : ?array;
    public function createOperation(string $structureExternalId, string $name, string $ownerEmail) : int;
    public function addResourceToOperation(int $externalOperationId, string $volunteerExternalId) : ?int;
}
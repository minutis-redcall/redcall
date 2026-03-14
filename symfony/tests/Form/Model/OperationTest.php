<?php

namespace App\Tests\Form\Model;

use App\Form\Model\Campaign;
use App\Form\Model\Operation;
use App\Form\Model\SmsTrigger;
use PHPUnit\Framework\TestCase;

class OperationTest extends TestCase
{
    public function testPublicPropertiesAreInitiallyNull(): void
    {
        $operation = new Operation();
        $this->assertNull($operation->structureExternalId);
        $this->assertNull($operation->name);
        $this->assertNull($operation->operationExternalId);
        $this->assertNull($operation->ownerExternalId);
        $this->assertNull($operation->campaign);
    }

    public function testSetStructureExternalId(): void
    {
        $operation = new Operation();
        $operation->structureExternalId = 42;
        $this->assertSame(42, $operation->structureExternalId);
    }

    public function testSetName(): void
    {
        $operation = new Operation();
        $operation->name = 'Test Operation';
        $this->assertSame('Test Operation', $operation->name);
    }

    public function testSetOperationExternalId(): void
    {
        $operation = new Operation();
        $operation->operationExternalId = 'EXT-123';
        $this->assertSame('EXT-123', $operation->operationExternalId);
    }

    public function testSetOwnerExternalId(): void
    {
        $operation = new Operation();
        $operation->ownerExternalId = 'OWNER-456';
        $this->assertSame('OWNER-456', $operation->ownerExternalId);
    }

    public function testSetCampaignReference(): void
    {
        $operation = new Operation();
        $campaign = new Campaign(new SmsTrigger());
        $operation->campaign = $campaign;
        $this->assertSame($campaign, $operation->campaign);
    }
}

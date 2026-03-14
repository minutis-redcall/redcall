<?php

namespace App\Tests\Entity;

use App\Entity\Campaign;
use App\Entity\Choice;
use App\Entity\Operation;
use PHPUnit\Framework\TestCase;

class OperationTest extends TestCase
{
    public function testAddChoiceSetsOperationOnChoice(): void
    {
        $operation = new Operation();
        $choice = new Choice();

        $result = $operation->addChoice($choice);

        $this->assertSame($operation, $result);
        $this->assertTrue($operation->hasChoice($choice));
        $this->assertSame($operation, $choice->getOperation());
    }

    public function testAddChoiceDoesNotDuplicate(): void
    {
        $operation = new Operation();
        $choice = new Choice();

        $operation->addChoice($choice);
        $operation->addChoice($choice);

        $this->assertCount(1, $operation->getChoices());
    }

    public function testRemoveChoiceUnsetsOperationOnChoice(): void
    {
        $operation = new Operation();
        $choice = new Choice();

        $operation->addChoice($choice);
        $this->assertTrue($operation->hasChoice($choice));

        $result = $operation->removeChoice($choice);

        $this->assertSame($operation, $result);
        $this->assertFalse($operation->hasChoice($choice));
        $this->assertNull($choice->getOperation());
    }

    public function testRemoveChoiceDoesNotUnsetOperationIfAlreadyChanged(): void
    {
        $operation1 = new Operation();
        $operation2 = new Operation();
        $choice = new Choice();

        $operation1->addChoice($choice);

        // Reassign choice to operation2 before removing from operation1
        $choice->setOperation($operation2);

        $operation1->removeChoice($choice);

        // The operation on the choice should remain operation2, not null
        $this->assertSame($operation2, $choice->getOperation());
    }

    public function testRemoveChoiceThatDoesNotExistIsNoOp(): void
    {
        $operation = new Operation();
        $choice = new Choice();

        $result = $operation->removeChoice($choice);
        $this->assertSame($operation, $result);
        $this->assertCount(0, $operation->getChoices());
    }

    public function testSetCampaignSetsOwningRelation(): void
    {
        $operation = new Operation();
        $campaign = new Campaign();

        $result = $operation->setCampaign($campaign);

        $this->assertSame($operation, $result);
        $this->assertSame($campaign, $operation->getCampaign());
        $this->assertSame($operation, $campaign->getOperation());
    }

    public function testSetCampaignNullUnsetsOwningRelation(): void
    {
        $operation = new Operation();
        $campaign = new Campaign();

        $operation->setCampaign($campaign);
        $this->assertSame($campaign, $operation->getCampaign());

        $operation->setCampaign(null);

        $this->assertNull($operation->getCampaign());
        $this->assertNull($campaign->getOperation());
    }

    public function testSetCampaignNullWhenAlreadyNullIsNoOp(): void
    {
        $operation = new Operation();

        $result = $operation->setCampaign(null);

        $this->assertSame($operation, $result);
        $this->assertNull($operation->getCampaign());
    }

    public function testSetCampaignDoesNotSetOwningIfAlreadyCorrect(): void
    {
        $operation = new Operation();
        $campaign = new Campaign();

        // Pre-set the operation on campaign so the condition in setCampaign
        // that checks ($campaign->getOperation() !== $this) is false.
        $campaign->setOperation($operation);

        $operation->setCampaign($campaign);

        $this->assertSame($campaign, $operation->getCampaign());
        $this->assertSame($operation, $campaign->getOperation());
    }
}

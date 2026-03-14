<?php

namespace App\Tests\Form\Model;

use App\Entity\Campaign as CampaignEntity;
use App\Form\Model\Campaign;
use App\Form\Model\Operation;
use App\Form\Model\SmsTrigger;
use PHPUnit\Framework\TestCase;

class CampaignTest extends TestCase
{
    private function createCampaign(): Campaign
    {
        return new Campaign(new SmsTrigger());
    }

    // --- constants ---

    public function testCreateOperationConstant(): void
    {
        $this->assertSame('create', Campaign::CREATE_OPERATION);
    }

    public function testUseOperationConstant(): void
    {
        $this->assertSame('use', Campaign::USE_OPERATION);
    }

    // --- constructor defaults ---

    public function testConstructorSetsDefaultType(): void
    {
        $campaign = $this->createCampaign();
        $this->assertSame(CampaignEntity::TYPE_GREEN, $campaign->type);
    }

    public function testConstructorSetsEmptyLabel(): void
    {
        $campaign = $this->createCampaign();
        $this->assertSame('', $campaign->label);
    }

    public function testConstructorSetsTrigger(): void
    {
        $trigger = new SmsTrigger();
        $campaign = new Campaign($trigger);
        $this->assertSame($trigger, $campaign->trigger);
    }

    public function testConstructorSetsHasOperationFalse(): void
    {
        $campaign = $this->createCampaign();
        $this->assertFalse($campaign->hasOperation);
    }

    public function testConstructorSetsCreateOperationDefault(): void
    {
        $campaign = $this->createCampaign();
        $this->assertSame(Campaign::CREATE_OPERATION, $campaign->createOperation);
    }

    public function testConstructorCreatesOperation(): void
    {
        $campaign = $this->createCampaign();
        $this->assertInstanceOf(Operation::class, $campaign->operation);
    }

    public function testConstructorLinksOperationToCampaign(): void
    {
        $campaign = $this->createCampaign();
        $this->assertSame($campaign, $campaign->operation->campaign);
    }

    // --- public properties ---

    public function testPublicPropertiesAreWritable(): void
    {
        $campaign = $this->createCampaign();

        $campaign->label = 'Test Label';
        $this->assertSame('Test Label', $campaign->label);

        $campaign->type = CampaignEntity::TYPE_RED;
        $this->assertSame(CampaignEntity::TYPE_RED, $campaign->type);

        $campaign->hasOperation = true;
        $this->assertTrue($campaign->hasOperation);

        $campaign->createOperation = Campaign::USE_OPERATION;
        $this->assertSame(Campaign::USE_OPERATION, $campaign->createOperation);
    }
}

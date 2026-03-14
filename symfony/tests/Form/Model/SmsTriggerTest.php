<?php

namespace App\Tests\Form\Model;

use App\Entity\Communication;
use App\Form\Model\SmsTrigger;
use PHPUnit\Framework\TestCase;

class SmsTriggerTest extends TestCase
{
    public function testConstructorSetsTypeSms(): void
    {
        $trigger = new SmsTrigger();
        $this->assertSame(Communication::TYPE_SMS, $trigger->getType());
    }

    public function testConstructorInitializesAudienceData(): void
    {
        $trigger = new SmsTrigger();
        $audience = $trigger->getAudience();
        $this->assertIsArray($audience);
        $this->assertArrayHasKey('volunteers', $audience);
    }

    public function testInheritsBaseTriggerMethods(): void
    {
        $trigger = new SmsTrigger();
        $trigger->setLabel('Test SMS');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Test message');
        $trigger->setAnswers(['Yes', 'No']);

        $this->assertSame('Test SMS', $trigger->getLabel());
        $this->assertSame('fr', $trigger->getLanguage());
        $this->assertSame('Test message', $trigger->getMessage());
        $this->assertSame(['Yes', 'No'], $trigger->getAnswers());
    }
}

<?php

namespace App\Tests\Form\Model;

use App\Entity\Communication;
use App\Form\Model\CallTrigger;
use PHPUnit\Framework\TestCase;

class CallTriggerTest extends TestCase
{
    public function testConstructorSetsTypeCall(): void
    {
        $trigger = new CallTrigger();
        $this->assertSame(Communication::TYPE_CALL, $trigger->getType());
    }

    public function testConstructorInitializesAudienceData(): void
    {
        $trigger = new CallTrigger();
        $audience = $trigger->getAudience();
        $this->assertIsArray($audience);
        $this->assertArrayHasKey('volunteers', $audience);
    }

    public function testInheritsBaseTriggerMethods(): void
    {
        $trigger = new CallTrigger();
        $trigger->setLabel('Test Call');
        $trigger->setLanguage('en');
        $trigger->setMessage('Call message');

        $this->assertSame('Test Call', $trigger->getLabel());
        $this->assertSame('en', $trigger->getLanguage());
        $this->assertSame('Call message', $trigger->getMessage());
    }
}

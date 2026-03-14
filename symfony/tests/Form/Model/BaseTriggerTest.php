<?php

namespace App\Tests\Form\Model;

use App\Form\Model\SmsTrigger;
use PHPUnit\Framework\TestCase;

/**
 * BaseTrigger is abstract, so we test it through SmsTrigger.
 */
class BaseTriggerTest extends TestCase
{
    private function createTrigger(): SmsTrigger
    {
        return new SmsTrigger();
    }

    // --- shortcut ---

    public function testShortcutDefaultsToNull(): void
    {
        $trigger = $this->createTrigger();
        $this->assertNull($trigger->getShortcut());
    }

    public function testSetAndGetShortcut(): void
    {
        $trigger = $this->createTrigger();
        $result = $trigger->setShortcut('SC');
        $this->assertSame('SC', $trigger->getShortcut());
        $this->assertSame($trigger, $result, 'setShortcut should return $this for fluent calls');
    }

    public function testSetShortcutToNull(): void
    {
        $trigger = $this->createTrigger();
        $trigger->setShortcut('SC');
        $trigger->setShortcut(null);
        $this->assertNull($trigger->getShortcut());
    }

    // --- label ---

    public function testSetAndGetLabel(): void
    {
        $trigger = $this->createTrigger();
        $result = $trigger->setLabel('Test Label');
        $this->assertSame('Test Label', $trigger->getLabel());
        $this->assertSame($trigger, $result);
    }

    // --- type ---

    public function testTypeIsSetByConstructor(): void
    {
        $trigger = $this->createTrigger();
        $this->assertSame('sms', $trigger->getType());
    }

    public function testSetAndGetType(): void
    {
        $trigger = $this->createTrigger();
        $result = $trigger->setType('call');
        $this->assertSame('call', $trigger->getType());
        $this->assertSame($trigger, $result);
    }

    // --- audience ---

    public function testAudienceDefaultsToEmptyData(): void
    {
        $trigger = $this->createTrigger();
        $audience = $trigger->getAudience();
        $this->assertIsArray($audience);
        // Should have the keys from AudienceType::createEmptyData
        $this->assertArrayHasKey('volunteers', $audience);
        $this->assertArrayHasKey('excluded_volunteers', $audience);
        $this->assertArrayHasKey('structures_global', $audience);
        $this->assertArrayHasKey('test_on_me', $audience);
    }

    public function testSetAndGetAudience(): void
    {
        $trigger = $this->createTrigger();
        $data = ['volunteers' => [1, 2, 3]];
        $result = $trigger->setAudience($data);
        $this->assertSame($data, $trigger->getAudience());
        $this->assertSame($trigger, $result);
    }

    // --- language ---

    public function testSetAndGetLanguage(): void
    {
        $trigger = $this->createTrigger();
        $result = $trigger->setLanguage('fr');
        $this->assertSame('fr', $trigger->getLanguage());
        $this->assertSame($trigger, $result);
    }

    // --- message ---

    public function testMessageDefaultsToNull(): void
    {
        $trigger = $this->createTrigger();
        $this->assertNull($trigger->getMessage());
    }

    public function testSetAndGetMessage(): void
    {
        $trigger = $this->createTrigger();
        $result = $trigger->setMessage('Hello world');
        $this->assertSame('Hello world', $trigger->getMessage());
        $this->assertSame($trigger, $result);
    }

    public function testSetMessageToNull(): void
    {
        $trigger = $this->createTrigger();
        $trigger->setMessage('Hello');
        $trigger->setMessage(null);
        $this->assertNull($trigger->getMessage());
    }

    // --- answers ---

    public function testAnswersDefaultsToEmptyArray(): void
    {
        $trigger = $this->createTrigger();
        $this->assertSame([], $trigger->getAnswers());
    }

    public function testSetAndGetAnswers(): void
    {
        $trigger = $this->createTrigger();
        $answers = ['Yes', 'No', 'Maybe'];
        $result = $trigger->setAnswers($answers);
        $this->assertSame($answers, $trigger->getAnswers());
        $this->assertSame($trigger, $result);
    }

    // --- multipleAnswer ---

    public function testMultipleAnswerDefaultsToFalse(): void
    {
        $trigger = $this->createTrigger();
        $this->assertFalse($trigger->isMultipleAnswer());
    }

    public function testSetAndGetMultipleAnswer(): void
    {
        $trigger = $this->createTrigger();
        $result = $trigger->setMultipleAnswer(true);
        $this->assertTrue($trigger->isMultipleAnswer());
        $this->assertSame($trigger, $result);
    }

    // --- operation ---

    public function testOperationDefaultsToFalse(): void
    {
        $trigger = $this->createTrigger();
        $this->assertFalse($trigger->isOperation());
    }

    public function testSetAndGetOperation(): void
    {
        $trigger = $this->createTrigger();
        $result = $trigger->setOperation(true);
        $this->assertTrue($trigger->isOperation());
        $this->assertSame($trigger, $result);
    }

    // --- operationAnswers ---

    public function testOperationAnswersDefaultsToEmptyArray(): void
    {
        $trigger = $this->createTrigger();
        $this->assertSame([], $trigger->getOperationAnswers());
    }

    public function testAddOperationAnswer(): void
    {
        $trigger = $this->createTrigger();
        $result = $trigger->addOperationAnswer('Answer1');
        $this->assertSame(['Answer1'], $trigger->getOperationAnswers());
        $this->assertSame($trigger, $result);
    }

    public function testAddMultipleOperationAnswers(): void
    {
        $trigger = $this->createTrigger();
        $trigger->addOperationAnswer('Answer1');
        $trigger->addOperationAnswer('Answer2');
        $this->assertSame(['Answer1', 'Answer2'], $trigger->getOperationAnswers());
    }

    /**
     * Note: BaseTrigger::removeOperationAnswer() declares return type `self` but
     * removeOperationAnswer removes an existing answer and returns $this for fluent chaining.
     */
    public function testRemoveOperationAnswerRemovesExisting(): void
    {
        $trigger = $this->createTrigger();
        $trigger->addOperationAnswer('Answer1');
        $trigger->addOperationAnswer('Answer2');

        $result = $trigger->removeOperationAnswer('Answer1');
        $this->assertSame($trigger, $result);
        $this->assertNotContains('Answer1', $trigger->getOperationAnswers());
        $this->assertContains('Answer2', $trigger->getOperationAnswers());
    }

    public function testRemoveNonExistentOperationAnswerIsNoOp(): void
    {
        $trigger = $this->createTrigger();
        $trigger->addOperationAnswer('Answer1');

        $result = $trigger->removeOperationAnswer('NonExistent');
        $this->assertSame($trigger, $result);
        $this->assertContains('Answer1', $trigger->getOperationAnswers());
    }

    // --- jsonSerialize ---

    public function testJsonSerialize(): void
    {
        $trigger = $this->createTrigger();
        $trigger->setLabel('Test');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Hello');
        $trigger->setAnswers(['Yes', 'No']);
        $trigger->setMultipleAnswer(true);

        $json = $trigger->jsonSerialize();

        $this->assertIsArray($json);
        $this->assertSame('Test', $json['label']);
        $this->assertSame('fr', $json['language']);
        $this->assertSame('Hello', $json['message']);
        $this->assertSame(['Yes', 'No'], $json['answers']);
        $this->assertTrue($json['multipleAnswer']);
        $this->assertSame('sms', $json['type']);
    }

    public function testJsonSerializeIsJsonEncodable(): void
    {
        $trigger = $this->createTrigger();
        $trigger->setLabel('Test');
        $trigger->setLanguage('fr');
        $trigger->setMessage('Hello');

        $encoded = json_encode($trigger);
        $this->assertNotFalse($encoded);
        $decoded = json_decode($encoded, true);
        $this->assertSame('Test', $decoded['label']);
    }
}

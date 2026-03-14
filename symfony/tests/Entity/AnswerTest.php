<?php

namespace App\Tests\Entity;

use App\Entity\Answer;
use App\Entity\Choice;
use DateTime;
use PHPUnit\Framework\TestCase;

class AnswerTest extends TestCase
{
    private function createChoice(string $label): Choice
    {
        $choice = new Choice();
        $choice->setLabel($label);

        return $choice;
    }

    public function testGetChoiceLabelsEmpty(): void
    {
        $answer = new Answer();

        $this->assertSame([], $answer->getChoiceLabels());
    }

    public function testGetChoiceLabelsReturnsLabelsInOrder(): void
    {
        $answer = new Answer();
        $answer->addChoice($this->createChoice('Yes'));
        $answer->addChoice($this->createChoice('No'));
        $answer->addChoice($this->createChoice('Maybe'));

        $this->assertSame(['Yes', 'No', 'Maybe'], $answer->getChoiceLabels());
    }

    public function testAddChoiceAddsNewChoice(): void
    {
        $answer = new Answer();
        $choice = $this->createChoice('Yes');

        $result = $answer->addChoice($choice);

        $this->assertSame($answer, $result, 'addChoice should return $this for fluent interface');
        $this->assertCount(1, $answer->getChoices());
        $this->assertTrue($answer->hasChoice($choice));
    }

    public function testAddChoiceDoesNotAddDuplicate(): void
    {
        $answer = new Answer();
        $choice = $this->createChoice('Yes');

        $answer->addChoice($choice);
        $answer->addChoice($choice);

        $this->assertCount(1, $answer->getChoices());
    }

    public function testAddMultipleDistinctChoices(): void
    {
        $answer = new Answer();
        $choiceA = $this->createChoice('Yes');
        $choiceB = $this->createChoice('No');

        $answer->addChoice($choiceA);
        $answer->addChoice($choiceB);

        $this->assertCount(2, $answer->getChoices());
        $this->assertTrue($answer->hasChoice($choiceA));
        $this->assertTrue($answer->hasChoice($choiceB));
    }

    public function testRemoveChoiceRemovesExisting(): void
    {
        $answer = new Answer();
        $choice = $this->createChoice('Yes');
        $answer->addChoice($choice);

        $result = $answer->removeChoice($choice);

        $this->assertSame($answer, $result, 'removeChoice should return $this for fluent interface');
        $this->assertCount(0, $answer->getChoices());
        $this->assertFalse($answer->hasChoice($choice));
    }

    public function testRemoveChoiceDoesNothingWhenAbsent(): void
    {
        $answer = new Answer();
        $choiceA = $this->createChoice('Yes');
        $choiceB = $this->createChoice('No');
        $answer->addChoice($choiceA);

        $answer->removeChoice($choiceB);

        $this->assertCount(1, $answer->getChoices());
        $this->assertTrue($answer->hasChoice($choiceA));
    }

    public function testRemoveChoiceFromEmptyCollection(): void
    {
        $answer = new Answer();
        $choice = $this->createChoice('Yes');

        $answer->removeChoice($choice);

        $this->assertCount(0, $answer->getChoices());
    }

    public function testOnPrePersistSetsUpdatedAtWhenNull(): void
    {
        $answer = new Answer();
        $this->assertNull($answer->getUpdatedAt());

        $before = new DateTime();
        $answer->onPrePersist();
        $after = new DateTime();

        $this->assertNotNull($answer->getUpdatedAt());
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $answer->getUpdatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $answer->getUpdatedAt()->getTimestamp());
    }

    public function testOnPrePersistDoesNotOverwriteExistingUpdatedAt(): void
    {
        $answer = new Answer();
        $existing = new DateTime('2020-01-01 12:00:00');
        $answer->setUpdatedAt($existing);

        $answer->onPrePersist();

        $this->assertSame($existing, $answer->getUpdatedAt());
    }

    public function testOnPreUpdateSetsUpdatedAtWhenNull(): void
    {
        $answer = new Answer();
        $this->assertNull($answer->getUpdatedAt());

        $before = new DateTime();
        $answer->onPreUpdate();
        $after = new DateTime();

        $this->assertNotNull($answer->getUpdatedAt());
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $answer->getUpdatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $answer->getUpdatedAt()->getTimestamp());
    }

    public function testOnPreUpdateDoesNotOverwriteExistingUpdatedAt(): void
    {
        $answer = new Answer();
        $existing = new DateTime('2020-06-15 08:30:00');
        $answer->setUpdatedAt($existing);

        $answer->onPreUpdate();

        $this->assertSame($existing, $answer->getUpdatedAt());
    }

    public function testGetChoiceLabelsAfterRemoval(): void
    {
        $answer = new Answer();
        $choiceA = $this->createChoice('Alpha');
        $choiceB = $this->createChoice('Beta');
        $choiceC = $this->createChoice('Gamma');

        $answer->addChoice($choiceA);
        $answer->addChoice($choiceB);
        $answer->addChoice($choiceC);

        $answer->removeChoice($choiceB);

        $labels = $answer->getChoiceLabels();
        $this->assertContains('Alpha', $labels);
        $this->assertNotContains('Beta', $labels);
        $this->assertContains('Gamma', $labels);
        $this->assertCount(2, $labels);
    }

    public function testIsValidWithNoChoices(): void
    {
        $answer = new Answer();
        $this->assertFalse($answer->isValid());
    }

    public function testIsValidWithChoices(): void
    {
        $answer = new Answer();
        $answer->addChoice($this->createChoice('Yes'));
        $this->assertTrue($answer->isValid());
    }
}

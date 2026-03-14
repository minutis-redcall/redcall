<?php

namespace App\Tests\Entity;

use App\Entity\PrefilledAnswers;
use PHPUnit\Framework\TestCase;

class PrefilledAnswersTest extends TestCase
{
    public function testSanitizePFAsReplacesCommas(): void
    {
        $pfa = new PrefilledAnswers();
        $pfa->setAnswers(['Yes, sure', 'No, thanks', 'Maybe']);

        // Trigger sanitize via the lifecycle callback
        $pfa->onPrePersist();

        $answers = $pfa->getAnswers();
        $this->assertSame('Yes#COM# sure', $answers[0]);
        $this->assertSame('No#COM# thanks', $answers[1]);
        $this->assertSame('Maybe', $answers[2]);
    }

    public function testRestorePFAsRestoresCommas(): void
    {
        $pfa = new PrefilledAnswers();
        $pfa->setAnswers(['Yes#COM# sure', 'No#COM# thanks', 'Maybe']);

        // Trigger restore via the lifecycle callback
        $pfa->onPostLoad();

        $answers = $pfa->getAnswers();
        $this->assertSame('Yes, sure', $answers[0]);
        $this->assertSame('No, thanks', $answers[1]);
        $this->assertSame('Maybe', $answers[2]);
    }

    public function testSanitizeAndRestoreRoundTrip(): void
    {
        $original = ['Answer with, commas', 'Plain answer', 'Another, one, here'];

        $pfa = new PrefilledAnswers();
        $pfa->setAnswers($original);

        // Sanitize (as if persisting)
        $pfa->onPrePersist();

        // Verify commas are gone from raw values
        foreach ($pfa->getAnswers() as $answer) {
            $this->assertStringNotContainsString(',', $answer);
        }

        // Restore (as if loading)
        $pfa->onPostLoad();

        $this->assertSame($original, $pfa->getAnswers());
    }

    public function testSanitizeWithNoCommasIsNoOp(): void
    {
        $pfa = new PrefilledAnswers();
        $pfa->setAnswers(['Yes', 'No', 'Maybe']);

        $pfa->onPrePersist();

        $this->assertSame(['Yes', 'No', 'Maybe'], $pfa->getAnswers());
    }

    public function testSanitizeWithEmptyAnswers(): void
    {
        $pfa = new PrefilledAnswers();
        $pfa->setAnswers([]);

        $pfa->onPrePersist();

        $this->assertSame([], $pfa->getAnswers());
    }

    public function testOnPreUpdateAlsoSanitizes(): void
    {
        $pfa = new PrefilledAnswers();
        $pfa->setAnswers(['Hello, world']);

        $pfa->onPreUpdate();

        $this->assertSame(['Hello#COM# world'], $pfa->getAnswers());
    }

    public function testMultipleCommasInSingleAnswer(): void
    {
        $pfa = new PrefilledAnswers();
        $pfa->setAnswers(['a, b, c, d']);

        $pfa->onPrePersist();

        $this->assertSame(['a#COM# b#COM# c#COM# d'], $pfa->getAnswers());

        $pfa->onPostLoad();

        $this->assertSame(['a, b, c, d'], $pfa->getAnswers());
    }
}

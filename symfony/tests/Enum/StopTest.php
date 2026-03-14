<?php

namespace App\Tests\Enum;

use App\Enum\Stop;
use PHPUnit\Framework\TestCase;

class StopTest extends TestCase
{
    public function testStopValue(): void
    {
        $enum = Stop::STOP();
        $this->assertSame('STOP', $enum->getValue());
    }

    public function testArretValue(): void
    {
        $enum = Stop::ARRET();
        $this->assertSame('ARRET', $enum->getValue());
    }

    public function testAllValuesExist(): void
    {
        $values = Stop::toArray();
        $this->assertCount(2, $values);
        $this->assertContains('STOP', $values);
        $this->assertContains('ARRET', $values);
    }

    // --- isValid (overridden to be case-insensitive) ---

    public function testIsValidWithUppercase(): void
    {
        $this->assertTrue(Stop::isValid('STOP'));
        $this->assertTrue(Stop::isValid('ARRET'));
    }

    public function testIsValidWithLowercase(): void
    {
        $this->assertTrue(Stop::isValid('stop'));
        $this->assertTrue(Stop::isValid('arret'));
    }

    public function testIsValidWithMixedCase(): void
    {
        $this->assertTrue(Stop::isValid('Stop'));
        $this->assertTrue(Stop::isValid('Arret'));
        $this->assertTrue(Stop::isValid('sToP'));
    }

    public function testIsValidWithInvalidValue(): void
    {
        $this->assertFalse(Stop::isValid('INVALID'));
        $this->assertFalse(Stop::isValid(''));
        $this->assertFalse(Stop::isValid('HALT'));
    }

    public function testEnumEquality(): void
    {
        $a = Stop::STOP();
        $b = Stop::STOP();
        $this->assertTrue($a->equals($b));
    }

    public function testEnumInequality(): void
    {
        $a = Stop::STOP();
        $b = Stop::ARRET();
        $this->assertFalse($a->equals($b));
    }

    public function testToString(): void
    {
        $this->assertSame('STOP', (string) Stop::STOP());
        $this->assertSame('ARRET', (string) Stop::ARRET());
    }
}

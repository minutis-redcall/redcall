<?php

namespace App\Tests\Tools;

use App\Tools\Random;
use PHPUnit\Framework\TestCase;

class RandomTest extends TestCase
{
    // --- generate ---

    public function testGenerateReturnsCorrectLength(): void
    {
        $result = Random::generate(16);

        $this->assertSame(16, strlen($result));
    }

    public function testGenerateReturnsAlphanumericByDefault(): void
    {
        $result = Random::generate(100);

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result);
    }

    public function testGenerateWithCustomBase(): void
    {
        // Use a base with enough ASCII chars to avoid slow matching
        // (a very small base like 'abc' has ~1.2% match rate in random bytes,
        // leading to many loop iterations)
        $base = 'abcdefghijklmnop';
        $result = Random::generate(20, $base);

        $this->assertSame(20, strlen($result));
        $this->assertMatchesRegularExpression('/^[a-p]+$/', $result);
    }

    public function testGenerateProducesDifferentResults(): void
    {
        $result1 = Random::generate(32);
        $result2 = Random::generate(32);

        // Extremely unlikely to be the same
        $this->assertNotSame($result1, $result2);
    }

    public function testGenerateSmallSize(): void
    {
        $result = Random::generate(1);

        $this->assertSame(1, strlen($result));
    }

    // --- between ---

    public function testBetweenReturnsValueInRange(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $result = Random::between(10, 100);
            $this->assertGreaterThanOrEqual(10, $result);
            $this->assertLessThan(100, $result);
        }
    }

    public function testBetweenWithSwappedArguments(): void
    {
        // When a > b, should swap and still return valid range
        for ($i = 0; $i < 50; $i++) {
            $result = Random::between(100, 10);
            $this->assertGreaterThanOrEqual(10, $result);
            $this->assertLessThan(100, $result);
        }
    }

    public function testBetweenReturnsInteger(): void
    {
        $result = Random::between(1, 1000);
        $this->assertIsInt($result);
    }

    // --- filtered (tested via public wrappers) ---

    public function testUpperalphanumericReturnsCorrectChars(): void
    {
        $result = Random::upperalphanumeric(100);

        $this->assertSame(100, strlen($result));
        $this->assertMatchesRegularExpression('/^[A-Z0-9]+$/', $result);
    }

    public function testUpperalphabeticReturnsCorrectChars(): void
    {
        $result = Random::upperalphabetic(100);

        $this->assertSame(100, strlen($result));
        $this->assertMatchesRegularExpression('/^[A-Z]+$/', $result);
    }

    public function testLoweralphanumericReturnsCorrectChars(): void
    {
        $result = Random::loweralphanumeric(100);

        $this->assertSame(100, strlen($result));
        $this->assertMatchesRegularExpression('/^[a-z0-9]+$/', $result);
    }

    public function testLoweralphabeticReturnsCorrectChars(): void
    {
        $result = Random::loweralphabetic(100);

        $this->assertSame(100, strlen($result));
        $this->assertMatchesRegularExpression('/^[a-z]+$/', $result);
    }

    public function testAlphanumericReturnsCorrectChars(): void
    {
        $result = Random::alphanumeric(100);

        $this->assertSame(100, strlen($result));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $result);
    }

    public function testNumericReturnsCorrectChars(): void
    {
        $result = Random::numeric(100);

        $this->assertSame(100, strlen($result));
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $result);
    }

    public function testFilteredReturnsExactLength(): void
    {
        // All filtered methods must respect size
        $this->assertSame(1, strlen(Random::numeric(1)));
        $this->assertSame(5, strlen(Random::upperalphabetic(5)));
        $this->assertSame(200, strlen(Random::alphanumeric(200)));
    }
}

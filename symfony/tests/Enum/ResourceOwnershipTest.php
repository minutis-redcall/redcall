<?php

namespace App\Tests\Enum;

use App\Enum\ResourceOwnership;
use PHPUnit\Framework\TestCase;

class ResourceOwnershipTest extends TestCase
{
    public function testKnownResourceValue(): void
    {
        $enum = ResourceOwnership::KNOWN_RESOURCE();
        $this->assertSame('KNOWN_RESOURCE', $enum->getValue());
    }

    public function testResolvedResourceValue(): void
    {
        $enum = ResourceOwnership::RESOLVED_RESOURCE();
        $this->assertSame('RESOLVED_RESOURCE', $enum->getValue());
    }

    public function testAllValuesExist(): void
    {
        $values = ResourceOwnership::toArray();
        $this->assertCount(2, $values);
        $this->assertContains('KNOWN_RESOURCE', $values);
        $this->assertContains('RESOLVED_RESOURCE', $values);
    }

    public function testEnumEquality(): void
    {
        $a = ResourceOwnership::KNOWN_RESOURCE();
        $b = ResourceOwnership::KNOWN_RESOURCE();
        $this->assertTrue($a->equals($b));
    }

    public function testEnumInequality(): void
    {
        $a = ResourceOwnership::KNOWN_RESOURCE();
        $b = ResourceOwnership::RESOLVED_RESOURCE();
        $this->assertFalse($a->equals($b));
    }

    public function testIsValidWithValidValue(): void
    {
        $this->assertTrue(ResourceOwnership::isValid('KNOWN_RESOURCE'));
        $this->assertTrue(ResourceOwnership::isValid('RESOLVED_RESOURCE'));
    }

    public function testIsValidWithInvalidValue(): void
    {
        $this->assertFalse(ResourceOwnership::isValid('INVALID'));
        $this->assertFalse(ResourceOwnership::isValid(''));
    }

    public function testToString(): void
    {
        $this->assertSame('KNOWN_RESOURCE', (string) ResourceOwnership::KNOWN_RESOURCE());
        $this->assertSame('RESOLVED_RESOURCE', (string) ResourceOwnership::RESOLVED_RESOURCE());
    }
}

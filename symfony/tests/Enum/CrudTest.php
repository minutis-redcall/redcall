<?php

namespace App\Tests\Enum;

use App\Enum\Crud;
use PHPUnit\Framework\TestCase;

class CrudTest extends TestCase
{
    public function testCreateValue(): void
    {
        $enum = Crud::CREATE();
        $this->assertSame('CREATE', $enum->getValue());
    }

    public function testReadValue(): void
    {
        $enum = Crud::READ();
        $this->assertSame('READ', $enum->getValue());
    }

    public function testUpdateValue(): void
    {
        $enum = Crud::UPDATE();
        $this->assertSame('UPDATE', $enum->getValue());
    }

    public function testDeleteValue(): void
    {
        $enum = Crud::DELETE();
        $this->assertSame('DELETE', $enum->getValue());
    }

    public function testAllValuesExist(): void
    {
        $values = Crud::toArray();
        $this->assertCount(4, $values);
        $this->assertContains('CREATE', $values);
        $this->assertContains('READ', $values);
        $this->assertContains('UPDATE', $values);
        $this->assertContains('DELETE', $values);
    }

    public function testEnumEquality(): void
    {
        $a = Crud::CREATE();
        $b = Crud::CREATE();
        $this->assertTrue($a->equals($b));
    }

    public function testEnumInequality(): void
    {
        $a = Crud::CREATE();
        $b = Crud::DELETE();
        $this->assertFalse($a->equals($b));
    }

    public function testIsValidWithValidValue(): void
    {
        $this->assertTrue(Crud::isValid('CREATE'));
        $this->assertTrue(Crud::isValid('READ'));
        $this->assertTrue(Crud::isValid('UPDATE'));
        $this->assertTrue(Crud::isValid('DELETE'));
    }

    public function testIsValidWithInvalidValue(): void
    {
        $this->assertFalse(Crud::isValid('INVALID'));
        $this->assertFalse(Crud::isValid(''));
        $this->assertFalse(Crud::isValid('create'));
    }

    public function testToString(): void
    {
        $this->assertSame('CREATE', (string) Crud::CREATE());
        $this->assertSame('READ', (string) Crud::READ());
    }
}

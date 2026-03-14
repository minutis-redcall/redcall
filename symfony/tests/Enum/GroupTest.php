<?php

namespace App\Tests\Enum;

use App\Enum\Group;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function testGroupConstants(): void
    {
        $this->assertSame('FF5733', Group::GROUP_1);
        $this->assertSame('33FF57', Group::GROUP_2);
        $this->assertSame('3357FF', Group::GROUP_3);
        $this->assertSame('FF33A1', Group::GROUP_4);
        $this->assertSame('A133FF', Group::GROUP_5);
        $this->assertSame('33FFF5', Group::GROUP_6);
        $this->assertSame('F5FF33', Group::GROUP_7);
        $this->assertSame('FF9633', Group::GROUP_8);
        $this->assertSame('3380FF', Group::GROUP_9);
        $this->assertSame('80FF33', Group::GROUP_10);
        $this->assertSame('FF3333', Group::GROUP_11);
        $this->assertSame('3333FF', Group::GROUP_12);
    }

    public function testGetGroupsReturnsAllValues(): void
    {
        $groups = Group::getGroups();

        $this->assertCount(12, $groups);
    }

    public function testGetGroupsReturnsIndexedArray(): void
    {
        $groups = Group::getGroups();

        // array_values should produce sequential integer keys
        $this->assertSame(array_values($groups), $groups);
    }

    public function testGetGroupsContainsAllColors(): void
    {
        $groups = Group::getGroups();

        $this->assertContains('FF5733', $groups);
        $this->assertContains('33FF57', $groups);
        $this->assertContains('3357FF', $groups);
        $this->assertContains('FF33A1', $groups);
        $this->assertContains('A133FF', $groups);
        $this->assertContains('33FFF5', $groups);
        $this->assertContains('F5FF33', $groups);
        $this->assertContains('FF9633', $groups);
        $this->assertContains('3380FF', $groups);
        $this->assertContains('80FF33', $groups);
        $this->assertContains('FF3333', $groups);
        $this->assertContains('3333FF', $groups);
    }

    public function testAllValuesAreValidHexColors(): void
    {
        $groups = Group::getGroups();

        foreach ($groups as $color) {
            $this->assertMatchesRegularExpression('/^[0-9A-F]{6}$/i', $color);
        }
    }

    public function testToArrayContains12Entries(): void
    {
        $this->assertCount(12, Group::toArray());
    }
}

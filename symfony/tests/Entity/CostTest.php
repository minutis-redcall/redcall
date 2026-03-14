<?php

namespace App\Tests\Entity;

use App\Entity\Cost;
use PHPUnit\Framework\TestCase;

class CostTest extends TestCase
{
    public function testOnPrePersistSetsCreatedAtWhenNull(): void
    {
        $cost = new Cost();
        $this->assertNull($cost->getCreatedAt());

        $cost->onPrePersist();

        $this->assertInstanceOf(\DateTimeInterface::class, $cost->getCreatedAt());
        // Should be roughly "now"
        $this->assertEqualsWithDelta(time(), $cost->getCreatedAt()->getTimestamp(), 2);
    }

    public function testOnPrePersistDoesNotOverrideExistingCreatedAt(): void
    {
        $cost = new Cost();
        $fixedDate = new \DateTime('2020-01-15 12:00:00');
        $cost->setCreatedAt($fixedDate);

        $cost->onPrePersist();

        $this->assertSame($fixedDate, $cost->getCreatedAt());
    }
}

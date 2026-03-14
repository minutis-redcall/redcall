<?php

namespace App\Tests\Entity;

use App\Entity\ReportRepartition;
use PHPUnit\Framework\TestCase;

class ReportRepartitionTest extends TestCase
{
    public function testGetCostsDecodesJson(): void
    {
        $repartition = new ReportRepartition();
        $costData = ['sms' => 0.75, 'call' => 1.20];
        $repartition->setCosts($costData);

        $this->assertSame($costData, $repartition->getCosts());
    }

    public function testGetCostsDefaultValue(): void
    {
        $repartition = new ReportRepartition();

        // Default is '[]' in the entity
        $this->assertSame([], $repartition->getCosts());
    }

    public function testGetCostsReturnsNullWhenFalsy(): void
    {
        $repartition = new ReportRepartition();

        // Simulate a falsy costs value by encoding empty string scenario
        // The default is '[]' which is truthy, so getCosts returns []
        // To test the null branch, we need a falsy value.
        // The only way to get null is if $this->costs is empty string or null.
        // We can use reflection to set it.
        $ref = new \ReflectionClass($repartition);
        $prop = $ref->getProperty('costs');
        $prop->setAccessible(true);
        $prop->setValue($repartition, '');

        $this->assertNull($repartition->getCosts());
    }

    public function testSetCostsEncodesJson(): void
    {
        $repartition = new ReportRepartition();
        $repartition->setCosts(['count' => 5, 'price' => 0.50]);

        $costs = $repartition->getCosts();
        $this->assertSame(5, $costs['count']);
        $this->assertSame(0.50, $costs['price']);
    }

    public function testSetCostsWithNestedData(): void
    {
        $repartition = new ReportRepartition();
        $costData = [
            'sms' => ['count' => 10, 'total' => 0.50],
            'call' => ['count' => 2, 'total' => 0.66],
        ];
        $repartition->setCosts($costData);

        $this->assertSame($costData, $repartition->getCosts());
    }

    public function testSetCostsEmptyArray(): void
    {
        $repartition = new ReportRepartition();
        $repartition->setCosts([]);

        $this->assertSame([], $repartition->getCosts());
    }
}

<?php

namespace App\Tests\Entity;

use App\Entity\Communication;
use App\Entity\Report;
use App\Entity\ReportRepartition;
use PHPUnit\Framework\TestCase;

class ReportTest extends TestCase
{
    public function testAddRepartitionSetsReportOnRepartition(): void
    {
        $report = new Report();
        $repartition = new ReportRepartition();

        $result = $report->addRepartition($repartition);

        $this->assertSame($report, $result);
        $this->assertCount(1, $report->getRepartitions());
        $this->assertSame($report, $repartition->getReport());
    }

    public function testAddRepartitionDoesNotDuplicate(): void
    {
        $report = new Report();
        $repartition = new ReportRepartition();

        $report->addRepartition($repartition);
        $report->addRepartition($repartition);

        $this->assertCount(1, $report->getRepartitions());
    }

    public function testRemoveRepartitionUnsetsReportOnRepartition(): void
    {
        $report = new Report();
        $repartition = new ReportRepartition();

        $report->addRepartition($repartition);
        $result = $report->removeRepartition($repartition);

        $this->assertSame($report, $result);
        $this->assertCount(0, $report->getRepartitions());
        $this->assertNull($repartition->getReport());
    }

    public function testRemoveRepartitionDoesNotUnsetReportIfAlreadyChanged(): void
    {
        $report1 = new Report();
        $report2 = new Report();
        $repartition = new ReportRepartition();

        $report1->addRepartition($repartition);
        $repartition->setReport($report2);

        $report1->removeRepartition($repartition);

        $this->assertSame($report2, $repartition->getReport());
    }

    public function testRemoveRepartitionThatDoesNotExistIsNoOp(): void
    {
        $report = new Report();
        $repartition = new ReportRepartition();

        $result = $report->removeRepartition($repartition);

        $this->assertSame($report, $result);
        $this->assertCount(0, $report->getRepartitions());
    }

    public function testGetCostsDecodesJson(): void
    {
        $report = new Report();
        $costData = ['sms' => 1.50, 'call' => 2.30];
        $report->setCosts($costData);

        $this->assertSame($costData, $report->getCosts());
    }

    public function testGetCostsDefaultValue(): void
    {
        $report = new Report();

        // Default is '[]' in the entity
        $this->assertSame([], $report->getCosts());
    }

    public function testSetCostsEncodesJson(): void
    {
        $report = new Report();
        $report->setCosts(['type' => 'sms', 'amount' => 0.05]);

        $costs = $report->getCosts();
        $this->assertSame('sms', $costs['type']);
        $this->assertSame(0.05, $costs['amount']);
    }

    public function testSetCommunicationSetsOwningRelation(): void
    {
        $report = new Report();
        $communication = new Communication();

        $result = $report->setCommunication($communication);

        $this->assertSame($report, $result);
        $this->assertSame($communication, $report->getCommunication());
        $this->assertSame($report, $communication->getReport());
    }

    public function testSetCommunicationNullUnsetsOwningRelation(): void
    {
        $report = new Report();
        $communication = new Communication();

        $report->setCommunication($communication);
        $report->setCommunication(null);

        $this->assertNull($report->getCommunication());
        $this->assertNull($communication->getReport());
    }

    public function testSetCommunicationNullWhenAlreadyNullIsNoOp(): void
    {
        $report = new Report();

        $result = $report->setCommunication(null);

        $this->assertSame($report, $result);
        $this->assertNull($report->getCommunication());
    }

    public function testSetCommunicationDoesNotSetOwningIfAlreadyCorrect(): void
    {
        $report = new Report();
        $communication = new Communication();

        $communication->setReport($report);
        $report->setCommunication($communication);

        $this->assertSame($communication, $report->getCommunication());
        $this->assertSame($report, $communication->getReport());
    }
}

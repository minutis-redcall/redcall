<?php

namespace App\Tests\Repository;

use App\Entity\Communication;
use App\Entity\Report;
use App\Repository\ReportRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReportRepositoryTest extends KernelTestCase
{
    /** @var ReportRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Report::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    private function createReport(Communication $communication, int $messageCount = 5, int $questionCount = 0): Report
    {
        $report = new Report();
        $report->setType($communication->getType());
        $report->setMessageCount($messageCount);
        $report->setQuestionCount($questionCount);
        $report->setAnswerCount(0);
        $report->setExchangeCount(0);
        $report->setErrorCount(0);
        $report->setCosts([]);

        $communication->setReport($report);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($report);
        $em->persist($communication);
        $em->flush();

        return $report;
    }

    // ── save ──

    public function testSave(): void
    {
        $campaign = $this->fixtures->createCampaign('Report Save Campaign');
        $comm = $this->fixtures->createCommunication($campaign);

        $report = new Report();
        $report->setType(Communication::TYPE_SMS);
        $report->setMessageCount(10);
        $report->setQuestionCount(2);
        $report->setAnswerCount(5);
        $report->setExchangeCount(3);
        $report->setErrorCount(0);
        $report->setCosts([]);

        $this->repository->save($report);

        $this->assertNotNull($report->getId());

        $found = $this->repository->find($report->getId());
        $this->assertNotNull($found);
        $this->assertSame(10, $found->getMessageCount());
    }

    // ── getCommunicationReportsBetween ──

    public function testGetCommunicationReportsBetween(): void
    {
        $campaign = $this->fixtures->createCampaign('Between Campaign');
        $comm = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'between body');
        $this->createReport($comm, 5, 0);

        $from = new \DateTime('-1 day');
        $to = new \DateTime('+1 day');

        $results = $this->repository->getCommunicationReportsBetween($from, $to, 3);

        $this->assertNotEmpty($results);
    }

    public function testGetCommunicationReportsBetweenExcludesSmallReports(): void
    {
        $campaign = $this->fixtures->createCampaign('Small Campaign');
        $comm = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'small body');
        $this->createReport($comm, 1, 0);

        $from = new \DateTime('-1 day');
        $to = new \DateTime('+1 day');

        $results = $this->repository->getCommunicationReportsBetween($from, $to, 5);

        // Our report with messageCount=1 should not appear with minMessages=5
        // (it may appear if there are other reports; we check our specific one)
        $foundSmall = false;
        foreach ($results as $report) {
            $this->assertGreaterThanOrEqual(5, $report->getMessageCount() + $report->getQuestionCount());
            if ($report->getMessageCount() === 1) {
                $foundSmall = true;
            }
        }
        $this->assertFalse($foundSmall, 'Small report should not appear in results');
    }

    public function testGetCommunicationReportsBetweenExcludesOutOfRange(): void
    {
        $campaign = $this->fixtures->createCampaign('OOR Campaign');
        $comm = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'oor body');
        $comm->setCreatedAt(new \DateTime('2020-01-01'));
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($comm);
        $em->flush();

        $this->createReport($comm, 10, 0);

        $from = new \DateTime('2025-01-01');
        $to = new \DateTime('2025-12-31');

        $results = $this->repository->getCommunicationReportsBetween($from, $to, 3);

        // Our 2020 report should not appear in a 2025 range
        $reportCommIds = [];
        foreach ($results as $report) {
            if ($report->getCommunication()) {
                $reportCommIds[] = $report->getCommunication()->getId();
            }
        }
        $this->assertNotContains($comm->getId(), $reportCommIds);
    }
}

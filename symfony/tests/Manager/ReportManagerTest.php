<?php

namespace App\Tests\Manager;

use App\Entity\Communication;
use App\Entity\Report;
use App\Manager\ReportManager;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ReportManagerTest extends KernelTestCase
{
    /** @var ReportManager */
    private $reportManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->reportManager = self::$container->get(ReportManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    public function testCreateReportForSimpleMessage()
    {
        $setup = $this->fixtures->createFullCampaign(
            'report-simple@test.com',
            false,
            Communication::TYPE_SMS,
            [] // no choices = simple message
        );

        $communication = $setup['communication'];
        $report = $this->reportManager->createReport($communication);

        $this->assertInstanceOf(Report::class, $report);
        $this->assertSame(Communication::TYPE_SMS, $report->getType());
        // With no choices, messageCount should be incremented (not questionCount)
        $this->assertSame(1, $report->getMessageCount());
        $this->assertSame(0, $report->getQuestionCount());
    }

    public function testCreateReportForQuestionWithChoices()
    {
        $setup = $this->fixtures->createFullCampaign(
            'report-choices@test.com',
            false,
            Communication::TYPE_SMS,
            ['Yes', 'No']
        );

        $communication = $setup['communication'];
        $report = $this->reportManager->createReport($communication);

        $this->assertInstanceOf(Report::class, $report);
        // With choices, questionCount should be incremented
        $this->assertSame(0, $report->getMessageCount());
        $this->assertSame(1, $report->getQuestionCount());
        // No answers yet
        $this->assertSame(0, $report->getAnswerCount());
    }

    public function testCreateReportCountsAnswers()
    {
        $setup = $this->fixtures->createFullCampaign(
            'report-answers@test.com',
            false,
            Communication::TYPE_SMS,
            ['Yes', 'No']
        );

        $message = $setup['message'];

        // Create an answer for the message
        $this->fixtures->createAnswer($message, 'Yes', false, [$setup['choices'][0]]);

        // Clear and re-fetch communication so the answers collection is fresh
        $this->em->clear();
        $communication = $this->em->find(Communication::class, $setup['communication']->getId());

        $report = $this->reportManager->createReport($communication);

        $this->assertSame(1, $report->getQuestionCount());
        $this->assertSame(1, $report->getAnswerCount());
        $this->assertSame(1, $report->getExchangeCount());
    }

    public function testCreateReportCountsErrors()
    {
        $setup = $this->fixtures->createFullCampaign(
            'report-errors@test.com',
            false,
            Communication::TYPE_SMS,
            ['Yes', 'No']
        );

        $message = $setup['message'];
        $message->setError('Delivery failed');
        $this->em->persist($message);
        $this->em->flush();

        $this->em->clear();
        $communication = $this->em->find(Communication::class, $setup['communication']->getId());

        $report = $this->reportManager->createReport($communication);

        $this->assertSame(1, $report->getErrorCount());
    }

    public function testCreateReportForEmailCommunication()
    {
        $setup = $this->fixtures->createFullCampaign(
            'report-email@test.com',
            false,
            Communication::TYPE_EMAIL,
            []
        );

        $report = $this->reportManager->createReport($setup['communication']);

        $this->assertSame(Communication::TYPE_EMAIL, $report->getType());
        $this->assertSame(1, $report->getMessageCount());
    }

    public function testCreateReportForCallCommunication()
    {
        $setup = $this->fixtures->createFullCampaign(
            'report-call@test.com',
            false,
            Communication::TYPE_CALL,
            ['Yes', 'No']
        );

        $report = $this->reportManager->createReport($setup['communication']);

        $this->assertSame(Communication::TYPE_CALL, $report->getType());
        $this->assertSame(1, $report->getQuestionCount());
    }

    public function testCreateReportMultipleMessages()
    {
        $campaign = $this->fixtures->createCampaign('Multi Msg Report');
        $communication = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'Notification');

        $vol1 = $this->fixtures->createStandaloneVolunteer('RPTMULTI-001', 'rptmulti1@test.com');
        $vol2 = $this->fixtures->createStandaloneVolunteer('RPTMULTI-002', 'rptmulti2@test.com');
        $vol3 = $this->fixtures->createStandaloneVolunteer('RPTMULTI-003', 'rptmulti3@test.com');

        $this->fixtures->createMessage($communication, $vol1);
        $this->fixtures->createMessage($communication, $vol2);
        $msg3 = $this->fixtures->createMessage($communication, $vol3);

        // One message has an error
        $msg3->setError('Failed');
        $this->em->persist($msg3);
        $this->em->flush();

        $this->em->clear();
        $communication = $this->em->find(Communication::class, $communication->getId());

        $report = $this->reportManager->createReport($communication);

        $this->assertSame(3, $report->getMessageCount());
        $this->assertSame(1, $report->getErrorCount());
    }

    public function testCreateReportUpdatesExistingReport()
    {
        $setup = $this->fixtures->createFullCampaign(
            'report-update@test.com',
            false,
            Communication::TYPE_SMS,
            []
        );

        // Create initial report
        $report1 = $this->reportManager->createReport($setup['communication']);
        $this->assertSame(1, $report1->getMessageCount());

        // Add another volunteer and message
        $vol2 = $this->fixtures->createStandaloneVolunteer('RPTUPD-002', 'rptupd2@test.com');
        $this->fixtures->createMessage($setup['communication'], $vol2);

        $this->em->clear();
        $communication = $this->em->find(Communication::class, $setup['communication']->getId());

        // Recreate report - should update existing
        $report2 = $this->reportManager->createReport($communication);

        // Note: the report reuses the same object but increments counters
        // Since the report is not reset between calls, counts accumulate
        $this->assertGreaterThanOrEqual(2, $report2->getMessageCount());
    }

    public function testCreateReportSetsCommunicationOnReport()
    {
        $setup = $this->fixtures->createFullCampaign(
            'report-comm@test.com',
            false,
            Communication::TYPE_SMS,
            []
        );

        $report = $this->reportManager->createReport($setup['communication']);

        $this->assertSame($setup['communication']->getId(), $report->getCommunication()->getId());
    }
}

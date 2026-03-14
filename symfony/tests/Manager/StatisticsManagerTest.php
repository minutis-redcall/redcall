<?php

namespace App\Tests\Manager;

use App\Entity\Communication;
use App\Manager\StatisticsManager;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StatisticsManagerTest extends KernelTestCase
{
    /** @var StatisticsManager */
    private $statisticsManager;

    /** @var DataFixtures */
    private $fixtures;

    /** @var \Doctrine\ORM\EntityManagerInterface */
    private $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->statisticsManager = self::$container->get(StatisticsManager::class);
        $this->em = self::$container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            self::$container->get('security.password_encoder')
        );
    }

    public function testGetDashboardStatisticsReturnsExpectedKeys()
    {
        // Create some minimal data to avoid division by zero
        $volunteer = $this->fixtures->createStandaloneVolunteer('STATS-001', 'stats@test.com');

        $from = new \DateTime('-30 days');
        $to = new \DateTime();

        $statistics = $this->statisticsManager->getDashboardStatistics($from, $to);

        $this->assertIsArray($statistics);
        $this->assertArrayHasKey('openCampaigns', $statistics);
        $this->assertArrayHasKey('campaignsPeriod', $statistics);
        $this->assertArrayHasKey('messagesSent', $statistics);
        $this->assertArrayHasKey('triggeredVolounteers', $statistics);
        $this->assertArrayHasKey('answersReceived', $statistics);
        $this->assertArrayHasKey('volunteers', $statistics);
        $this->assertArrayHasKey('pegassUpdates', $statistics);
    }

    public function testGetDashboardStatisticsOpenCampaignsCount()
    {
        // Create an active campaign
        $this->fixtures->createCampaign('Stats Active Campaign');

        $from = new \DateTime('-30 days');
        $to = new \DateTime('+1 day');

        $statistics = $this->statisticsManager->getDashboardStatistics($from, $to);

        $this->assertGreaterThanOrEqual(1, $statistics['openCampaigns']);
    }

    public function testGetDashboardStatisticsMessagesSentStructure()
    {
        $from = new \DateTime('-30 days');
        $to = new \DateTime('+1 day');

        $statistics = $this->statisticsManager->getDashboardStatistics($from, $to);

        $this->assertArrayHasKey('totalCount', $statistics['messagesSent']);
        $this->assertIsInt($statistics['messagesSent']['totalCount']);
    }

    public function testGetDashboardStatisticsVolunteersStructure()
    {
        $from = new \DateTime('-30 days');
        $to = new \DateTime('+1 day');

        $statistics = $this->statisticsManager->getDashboardStatistics($from, $to);

        $this->assertArrayHasKey('total', $statistics['volunteers']);
        $this->assertArrayHasKey('email', $statistics['volunteers']);
        $this->assertArrayHasKey('phone', $statistics['volunteers']);
        $this->assertArrayHasKey('both', $statistics['volunteers']);

        // Each should have number and percent
        $this->assertArrayHasKey('number', $statistics['volunteers']['total']);
        $this->assertArrayHasKey('percent', $statistics['volunteers']['total']);
    }

    public function testGetDashboardStatisticsPegassUpdatesStructure()
    {
        $from = new \DateTime('-30 days');
        $to = new \DateTime('+1 day');

        $statistics = $this->statisticsManager->getDashboardStatistics($from, $to);

        $this->assertArrayHasKey('structures', $statistics['pegassUpdates']);
        $this->assertArrayHasKey('volunteers', $statistics['pegassUpdates']);
    }

    public function testGetDashboardStatisticsWithCampaignData()
    {
        // Create a full campaign to populate statistics
        $setup = $this->fixtures->createFullCampaign(
            'statsdata@test.com',
            false,
            Communication::TYPE_SMS,
            ['Yes', 'No']
        );

        $from = new \DateTime('-1 day');
        $to = new \DateTime('+1 day');

        $statistics = $this->statisticsManager->getDashboardStatistics($from, $to);

        // Should have at least 1 campaign in the period
        $this->assertGreaterThanOrEqual(1, $statistics['campaignsPeriod']);
    }
}

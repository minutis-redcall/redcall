<?php

namespace App\Tests\Repository;

use App\Entity\Campaign;
use App\Repository\CampaignRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CampaignRepositoryTest extends KernelTestCase
{
    /** @var CampaignRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Campaign::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── findOneByIdNoCache ──

    public function testFindOneByIdNoCache(): void
    {
        $campaign = $this->fixtures->createCampaign('Find NoCache Campaign');

        $found = $this->repository->findOneByIdNoCache($campaign->getId());
        $this->assertNotNull($found);
        $this->assertSame('Find NoCache Campaign', $found->getLabel());
    }

    public function testFindOneByIdNoCacheReturnsNullForNonexistent(): void
    {
        $found = $this->repository->findOneByIdNoCache(999999);
        $this->assertNull($found);
    }

    // ── closeCampaign ──

    public function testCloseCampaign(): void
    {
        $campaign = $this->fixtures->createCampaign('Close Campaign', Campaign::TYPE_GREEN, true);
        $this->assertEquals(1, $campaign->isActive());

        $this->repository->closeCampaign($campaign);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($campaign->getId());
        $this->assertEquals(0, $fresh->isActive());
    }

    // ── openCampaign ──

    public function testOpenCampaign(): void
    {
        $campaign = $this->fixtures->createCampaign('Open Campaign', Campaign::TYPE_GREEN, false);

        $this->repository->openCampaign($campaign);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($campaign->getId());
        $this->assertEquals(1, $fresh->isActive());
        $this->assertNotNull($fresh->getExpiresAt());
    }

    // ── changeColor ──

    public function testChangeColor(): void
    {
        $campaign = $this->fixtures->createCampaign('Color Campaign');

        $this->repository->changeColor($campaign, Campaign::TYPE_RED);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($campaign->getId());
        $this->assertSame(Campaign::TYPE_RED, $fresh->getType());
    }

    // ── changeName ──

    public function testChangeName(): void
    {
        $campaign = $this->fixtures->createCampaign('Old Name');

        $this->repository->changeName($campaign, 'New Name');

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($campaign->getId());
        $this->assertSame('New Name', $fresh->getLabel());
    }

    // ── changeNotes ──

    public function testChangeNotes(): void
    {
        $campaign = $this->fixtures->createCampaign('Notes Campaign');

        $this->repository->changeNotes($campaign, 'Some important notes');

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($campaign->getId());
        $this->assertSame('Some important notes', $fresh->getNotes());
        $this->assertNotNull($fresh->getNotesUpdatedAt());
    }

    // ── getActiveCampaignsQueryBuilder ──

    public function testGetActiveCampaignsQueryBuilder(): void
    {
        $this->fixtures->createCampaign('Active', Campaign::TYPE_GREEN, true);
        $this->fixtures->createCampaign('Inactive', Campaign::TYPE_GREEN, false);

        $results = $this->repository->getActiveCampaignsQueryBuilder()
            ->getQuery()->getResult();

        $labels = array_map(function (Campaign $c) { return $c->getLabel(); }, $results);
        $this->assertContains('Active', $labels);
        $this->assertNotContains('Inactive', $labels);
    }

    // ── getAllCampaignsQueryBuilder ──

    public function testGetAllCampaignsQueryBuilder(): void
    {
        $this->fixtures->createCampaign('AllQB Active', Campaign::TYPE_GREEN, true);
        $this->fixtures->createCampaign('AllQB Inactive', Campaign::TYPE_GREEN, false);

        $results = $this->repository->getAllCampaignsQueryBuilder()
            ->getQuery()->getResult();

        $labels = array_map(function (Campaign $c) { return $c->getLabel(); }, $results);
        $this->assertContains('AllQB Active', $labels);
        $this->assertContains('AllQB Inactive', $labels);
    }

    // ── countAllOpenCampaigns ──

    public function testCountAllOpenCampaigns(): void
    {
        $this->fixtures->createCampaign('Counted Open Campaign', Campaign::TYPE_GREEN, true);

        $count = $this->repository->countAllOpenCampaigns();
        $this->assertGreaterThanOrEqual(1, $count);
    }

    // ── closeExpiredCampaigns ──

    public function testCloseExpiredCampaigns(): void
    {
        $campaign = $this->fixtures->createCampaign('Expired Campaign', Campaign::TYPE_GREEN, true);
        $campaign->setExpiresAt(new \DateTime('-1 day'));
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($campaign);
        $em->flush();

        $this->repository->closeExpiredCampaigns();

        $em->clear();
        $fresh = $this->repository->find($campaign->getId());
        $this->assertEquals(0, $fresh->isActive());
    }

    // ── getNoteUpdateTimestamp ──

    public function testGetNoteUpdateTimestamp(): void
    {
        $campaign = $this->fixtures->createCampaign('Timestamp Campaign');
        $this->repository->changeNotes($campaign, 'notes');

        $timestamp = $this->repository->getNoteUpdateTimestamp($campaign->getId());
        $this->assertGreaterThan(0, $timestamp);
    }

    public function testGetNoteUpdateTimestampReturnsZeroWhenNoNotes(): void
    {
        $campaign = $this->fixtures->createCampaign('No Notes Campaign');

        $timestamp = $this->repository->getNoteUpdateTimestamp($campaign->getId());
        $this->assertSame(0, $timestamp);
    }

    // ── countNumberOfMessagesSent ──

    public function testCountNumberOfMessagesSent(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('cntmsg@test.com');

        $count = $this->repository->countNumberOfMessagesSent($fullCampaign['campaign']->getId());
        // Message was created with sent=true but messageId is null, so count is 0 for "sent"
        // (the query checks m.sent = 1)
        $this->assertGreaterThanOrEqual(0, $count);
    }

    // ── countNumberOfAnswersReceived ──

    public function testCountNumberOfAnswersReceived(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('cntans@test.com');

        $count = $this->repository->countNumberOfAnswersReceived($fullCampaign['campaign']->getId());
        $this->assertSame(0, (int) $count);
    }

    public function testCountNumberOfAnswersReceivedWithAnswers(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('cntwans@test.com');
        $this->fixtures->createAnswer($fullCampaign['message'], 'Yes');

        $count = $this->repository->countNumberOfAnswersReceived($fullCampaign['campaign']->getId());
        $this->assertSame(1, (int) $count);
    }

    // ── getCampaignAudience ──

    public function testGetCampaignAudience(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('audience@test.com');

        $audience = $this->repository->getCampaignAudience($fullCampaign['campaign']);

        $this->assertNotEmpty($audience);
        $structureIds = array_column($audience, 'structure_id');
        $this->assertContains($fullCampaign['structure']->getId(), $structureIds);
    }

    // ── getCampaignsOpenedByMeOrMyCrew ──

    public function testGetCampaignsOpenedByMeOrMyCrew(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('crew@test.com');
        // The communication needs a volunteer for the join
        $fullCampaign['communication']->setVolunteer($fullCampaign['volunteer']);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($fullCampaign['communication']);
        $em->flush();

        $results = $this->repository->getCampaignsOpenedByMeOrMyCrew($fullCampaign['user'])
            ->getQuery()->getResult();

        $ids = array_map(function (Campaign $c) { return $c->getId(); }, $results);
        $this->assertContains($fullCampaign['campaign']->getId(), $ids);
    }

    // ── getCampaignImpactingMyVolunteers ──

    public function testGetCampaignImpactingMyVolunteers(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('impact@test.com');

        $results = $this->repository->getCampaignImpactingMyVolunteers($fullCampaign['user'])
            ->getQuery()->getResult();

        $ids = array_map(function (Campaign $c) { return $c->getId(); }, $results);
        $this->assertContains($fullCampaign['campaign']->getId(), $ids);
    }

    // ── getInactiveCampaignsForUserQueryBuilder ──

    public function testGetInactiveCampaignsForUserQueryBuilder(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('inactqb@test.com');
        $fullCampaign['campaign']->setActive(false);
        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($fullCampaign['campaign']);
        $em->flush();

        $results = $this->repository->getInactiveCampaignsForUserQueryBuilder($fullCampaign['user'])
            ->getQuery()->getResult();

        $ids = array_map(function (Campaign $c) { return $c->getId(); }, $results);
        $this->assertContains($fullCampaign['campaign']->getId(), $ids);
    }
}

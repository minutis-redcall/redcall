<?php

namespace App\Tests\Repository;

use App\Entity\Communication;
use App\Repository\CommunicationRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class CommunicationRepositoryTest extends KernelTestCase
{
    /** @var CommunicationRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(Communication::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── changeName ──

    public function testChangeName(): void
    {
        $campaign = $this->fixtures->createCampaign('Comm Name Campaign');
        $comm = $this->fixtures->createCommunication($campaign, Communication::TYPE_SMS, 'body');

        $this->repository->changeName($comm, 'New Label');

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $fresh = $this->repository->find($comm->getId());
        $this->assertSame('New Label', $fresh->getLabel());
    }

    // ── findCommunicationIdsRequiringReports ──

    public function testFindCommunicationIdsRequiringReports(): void
    {
        $campaign = $this->fixtures->createCampaign('Report Req Campaign');
        $comm = $this->fixtures->createCommunication($campaign);

        // Communication was created just now with lastActivityAt = null and createdAt = now.
        // The query finds comms where (lastActivityAt < date) OR (lastActivityAt IS NULL AND createdAt < date)
        // AND report IS NULL. Since lastActivityAt is null and createdAt is now, use a future threshold.
        $ids = $this->repository->findCommunicationIdsRequiringReports(new \DateTime('+1 day'));

        $this->assertContains($comm->getId(), $ids);
    }

    public function testFindCommunicationIdsRequiringReportsExcludesRecentOnes(): void
    {
        $campaign = $this->fixtures->createCampaign('Recent Comm Campaign');
        $comm = $this->fixtures->createCommunication($campaign);
        // Communication was just created, should not be in results for future date
        $ids = $this->repository->findCommunicationIdsRequiringReports(new \DateTime('-1 day'));

        // This comm was just created (now), so createdAt > -1 day. It should NOT be in results.
        $this->assertNotContains($comm->getId(), $ids);
    }

    // ── getCommunicationStructures ──

    public function testGetCommunicationStructures(): void
    {
        $fullCampaign = $this->fixtures->createFullCampaign('commstr@test.com');

        $structureIds = $this->repository->getCommunicationStructures($fullCampaign['communication']);

        $this->assertContains($fullCampaign['structure']->getId(), $structureIds);
    }

    // ── clearEntityManager ──

    public function testClearEntityManager(): void
    {
        // Just verify it does not throw
        $this->repository->clearEntityManager();
        $this->assertTrue(true);
    }
}

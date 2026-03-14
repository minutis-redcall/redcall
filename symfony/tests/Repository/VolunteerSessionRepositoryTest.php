<?php

namespace App\Tests\Repository;

use App\Entity\VolunteerSession;
use App\Repository\VolunteerSessionRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VolunteerSessionRepositoryTest extends KernelTestCase
{
    /** @var VolunteerSessionRepository */
    private $repository;

    /** @var DataFixtures */
    private $fixtures;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = self::$container->get('doctrine.orm.entity_manager')
            ->getRepository(VolunteerSession::class);

        $this->fixtures = new DataFixtures(
            self::$container->get('doctrine.orm.entity_manager'),
            self::$container->get('security.password_encoder')
        );
    }

    // ── basic CRUD via findOneBy ──

    public function testFindOneBySessionId(): void
    {
        $data = $this->fixtures->createVolunteerWithSession('VSESS-001', 'vsess@test.com');
        $sessionId = $data['session']->getSessionId();

        $found = $this->repository->findOneBy(['sessionId' => $sessionId]);
        $this->assertNotNull($found);
        $this->assertSame($data['volunteer']->getId(), $found->getVolunteer()->getId());
    }

    // ── clearExpired ──

    public function testClearExpiredRemovesOldSessions(): void
    {
        $vol = $this->fixtures->createStandaloneVolunteer('VSESS-EXP-001', 'vsessexp@test.com');

        $session = new VolunteerSession();
        $session->setVolunteer($vol);
        $session->setSessionId('expired-session-id-123');
        $session->setCreatedAt(new \DateTime('-2 hours'));

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->persist($session);
        $em->flush();

        $id = $session->getId();

        // Clear sessions older than 1 hour (3600 seconds)
        $this->repository->clearExpired(3600);

        $em->clear();
        $found = $this->repository->find($id);
        $this->assertNull($found);
    }

    public function testClearExpiredKeepsRecentSessions(): void
    {
        $data = $this->fixtures->createVolunteerWithSession('VSESS-KEEP-001', 'vsesskeep@test.com');
        $id = $data['session']->getId();

        // Clear sessions older than 1 hour; our session was just created
        $this->repository->clearExpired(3600);

        $em = self::$container->get('doctrine.orm.entity_manager');
        $em->clear();
        $found = $this->repository->find($id);
        $this->assertNotNull($found);
    }
}

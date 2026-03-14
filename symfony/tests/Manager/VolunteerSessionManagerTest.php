<?php

namespace App\Tests\Manager;

use App\Entity\VolunteerSession;
use App\Manager\VolunteerSessionManager;
use App\Repository\VolunteerSessionRepository;
use App\Tests\Fixtures\DataFixtures;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VolunteerSessionManagerTest extends KernelTestCase
{
    private VolunteerSessionManager $manager;
    private VolunteerSessionRepository $repository;
    private DataFixtures $fixtures;

    protected function setUp() : void
    {
        self::bootKernel();

        $container        = static::getContainer();
        $this->manager    = $container->get(VolunteerSessionManager::class);
        $this->repository = $container->get('doctrine')->getRepository(VolunteerSession::class);
        $this->fixtures   = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_encoder')
        );
    }

    public function testCreateSessionReturnsSessionId()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('SESS_VOL_001', 'session_vol@example.com');

        $sessionId = $this->manager->createSession($volunteer);

        $this->assertNotEmpty($sessionId);
        $this->assertIsString($sessionId);
    }

    public function testCreateSessionStoresSessionInDatabase()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('SESS_VOL_002', 'session_vol2@example.com');

        $sessionId = $this->manager->createSession($volunteer);

        $storedSession = $this->repository->findOneBy(['sessionId' => $sessionId]);

        $this->assertNotNull($storedSession);
        $this->assertSame($volunteer->getId(), $storedSession->getVolunteer()->getId());
        $this->assertInstanceOf(\DateTimeInterface::class, $storedSession->getCreatedAt());
    }

    public function testCreateSessionReturnsSameIdOnSecondCall()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('SESS_VOL_003', 'session_vol3@example.com');

        $sessionId1 = $this->manager->createSession($volunteer);
        $sessionId2 = $this->manager->createSession($volunteer);

        // Second call returns the same session ID (from the HTTP session)
        $this->assertSame($sessionId1, $sessionId2);
    }

    public function testRemoveSessionDeletesFromDatabase()
    {
        $data = $this->fixtures->createVolunteerWithSession('SESS_VOL_004', 'session_vol4@example.com');
        $session = $data['session'];

        $sessionId = $session->getSessionId();

        $this->manager->removeSession($session);

        $storedSession = $this->repository->findOneBy(['sessionId' => $sessionId]);
        $this->assertNull($storedSession);
    }

    public function testClearExpiredRemovesOldSessions()
    {
        $volunteer = $this->fixtures->createStandaloneVolunteer('SESS_VOL_005', 'session_vol5@example.com');

        // Create an old session directly
        $oldSession = new VolunteerSession();
        $oldSession->setVolunteer($volunteer);
        $oldSession->setSessionId('old-session-' . bin2hex(random_bytes(8)));
        $oldSession->setCreatedAt(new \DateTime('-2 days'));

        $em = $this->fixtures->getEntityManager();
        $em->persist($oldSession);
        $em->flush();

        $sessionId = $oldSession->getSessionId();

        // Verify it exists
        $found = $this->repository->findOneBy(['sessionId' => $sessionId]);
        $this->assertNotNull($found);

        // Clear expired (TTL is 86400 seconds = 1 day)
        $this->manager->clearExpired();

        // Verify it was removed
        $em->clear();
        $found = $this->repository->findOneBy(['sessionId' => $sessionId]);
        $this->assertNull($found);
    }

    public function testClearExpiredKeepsRecentSessions()
    {
        $data = $this->fixtures->createVolunteerWithSession('SESS_VOL_006', 'session_vol6@example.com');
        $session = $data['session'];

        $sessionId = $session->getSessionId();

        $this->manager->clearExpired();

        $found = $this->repository->findOneBy(['sessionId' => $sessionId]);
        $this->assertNotNull($found);
    }
}

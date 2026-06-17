<?php

namespace App\Tests\Manager;

use App\Entity\VolunteerAuditLog;
use App\Manager\VolunteerAuditLogManager;
use App\Manager\VolunteerManager;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class VolunteerAuditLogManagerTest extends KernelTestCase
{
    private VolunteerAuditLogManager $manager;
    private VolunteerManager $volunteerManager;
    private DataFixtures $fixtures;
    private EntityManagerInterface $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->manager          = $container->get(VolunteerAuditLogManager::class);
        $this->volunteerManager = $container->get(VolunteerManager::class);
        $this->em               = $container->get('doctrine.orm.entity_manager');
        $this->fixtures         = new DataFixtures(
            $this->em,
            $container->get('security.password_hasher')
        );
    }

    public function testBuildSnapshotIsPiiFreeAndStructural()
    {
        $data      = $this->fixtures->createUserWithVolunteerAndStructure('audit-pii@test.com', false);
        $volunteer = $data['volunteer'];
        $structure = $data['structure'];

        $snapshot = $this->manager->buildSnapshot($volunteer);

        $this->assertSame($volunteer->getExternalId(), $snapshot['externalId']);
        $this->assertSame([$structure->getExternalId()], $snapshot['structures']);
        $this->assertIsArray($snapshot['badges']);
        $this->assertTrue($snapshot['hadBoundUser']);
        $this->assertSame((bool) $volunteer->isEnabled(), $snapshot['isEnabled']);
        $this->assertSame((bool) $volunteer->isLocked(), $snapshot['isLocked']);
        $this->assertArrayHasKey('lastSyncedAt', $snapshot);

        // Strict PII guard: none of these fields must ever land in the audit row.
        foreach (['firstName', 'lastName', 'email', 'phone', 'phones', 'displayName'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $snapshot, sprintf('snapshot leaked PII field %s', $forbidden));
        }
    }

    public function testLogAnonymizedPersistsRowWithActorAndTargetExternalId()
    {
        $admin     = $this->fixtures->createRawUser('audit-vol-admin@test.com', 'password', true);
        $data      = $this->fixtures->createUserWithVolunteerAndStructure('audit-vol-target@test.com', false);
        $volunteer = $data['volunteer'];

        $snapshot = $this->manager->buildSnapshot($volunteer);

        $log = $this->manager->logAnonymized($admin, 'admin: manual', $volunteer, $snapshot, $data['user']);

        $this->assertInstanceOf(VolunteerAuditLog::class, $log);
        $this->assertNotNull($log->getId());
        $this->assertSame('anonymize', $log->getAction());
        $this->assertSame($admin->getId(), $log->getActor()->getId());
        $this->assertSame($admin->getDisplayName(), $log->getActorLabel());
        $this->assertSame($volunteer->getExternalId(), $log->getTargetExternalId());
        $this->assertSame($data['user']->getId(), $log->getTargetBoundUserId());
    }

    public function testActorLabelFallsBackToCliLabelThenSystem()
    {
        $data      = $this->fixtures->createUserWithVolunteerAndStructure('audit-vol-cli@test.com', false);
        $volunteer = $data['volunteer'];
        $snapshot  = $this->manager->buildSnapshot($volunteer);

        $cliRow = $this->manager->logAnonymized(null, 'sync: stale', $volunteer, $snapshot);
        $this->assertSame('sync: stale', $cliRow->getActorLabel());

        $sysRow = $this->manager->logAnonymized(null, null, $volunteer, $snapshot);
        $this->assertSame('system', $sysRow->getActorLabel());
    }

    public function testVolunteerManagerAnonymizeWritesAuditRowWithRealNivol()
    {
        $data       = $this->fixtures->createUserWithVolunteerAndStructure('audit-flow@test.com', false);
        $volunteer  = $data['volunteer'];
        $originalId = $volunteer->getExternalId();

        $this->volunteerManager->anonymize($volunteer, null, 'sync: stale');

        $this->em->clear();

        $rows = $this->em->getRepository(VolunteerAuditLog::class)->findBy(['targetExternalId' => $originalId]);
        $this->assertCount(1, $rows, 'one audit row written per anonymize');

        $row = $rows[0];
        $this->assertSame('anonymize', $row->getAction());
        $this->assertSame('sync: stale', $row->getActorLabel());
        $this->assertSame($originalId, $row->getTargetExternalId(),
            'NIVOL captured must be the pre-anonymize one, not deleted-XXX');
        $this->assertNotNull($row->getTargetBoundUserId());

        $snapshot = $row->getSnapshot();
        $this->assertSame($originalId, $snapshot['externalId']);
        $this->assertTrue($snapshot['hadBoundUser']);
    }

    public function testVolunteerManagerAnonymizeSkippedDoesNotWriteAuditRow()
    {
        // user.locked = true ⇒ anonymize early-returns ⇒ nothing to log
        $data      = $this->fixtures->createUserWithVolunteerAndStructure('audit-locked@test.com', false);
        $volunteer = $data['volunteer'];
        $data['user']->setLocked(true);
        $this->em->persist($data['user']);
        $this->em->flush();

        $originalId = $volunteer->getExternalId();
        $this->volunteerManager->anonymize($volunteer, null, 'sync: stale');

        $this->em->clear();
        $rows = $this->em->getRepository(VolunteerAuditLog::class)->findBy(['targetExternalId' => $originalId]);
        $this->assertCount(0, $rows);
    }
}

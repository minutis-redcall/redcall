<?php

namespace App\Tests\Manager;

use App\Entity\UserAuditLog;
use App\Manager\UserAuditLogManager;
use App\Tests\Fixtures\DataFixtures;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UserAuditLogManagerTest extends KernelTestCase
{
    private UserAuditLogManager $manager;
    private DataFixtures $fixtures;
    private EntityManagerInterface $em;

    protected function setUp() : void
    {
        self::bootKernel();

        $container = static::getContainer();

        $this->manager  = $container->get(UserAuditLogManager::class);
        $this->em       = $container->get('doctrine.orm.entity_manager');
        $this->fixtures = new DataFixtures(
            $this->em,
            $container->get('security.password_hasher')
        );
    }

    public function testBuildSnapshotCapturesEveryAuditedField()
    {
        $data      = $this->fixtures->createUserWithVolunteerAndStructure('snap@test.com', true);
        $user      = $data['user'];
        $structure = $data['structure'];

        $snapshot = $this->manager->buildSnapshot($user);

        $this->assertSame('snap@test.com', $snapshot['username']);
        $this->assertSame($user->getExternalId(), $snapshot['externalId']);
        $this->assertSame($user->getDisplayName(), $snapshot['displayName']);
        $this->assertTrue($snapshot['isAdmin']);
        $this->assertTrue($snapshot['isVerified']);
        $this->assertTrue($snapshot['isTrusted']);
        $this->assertFalse($snapshot['locked']);
        $this->assertNotEmpty($snapshot['passwordFingerprint']);

        $this->assertCount(1, $snapshot['structures']);
        $this->assertSame($structure->getId(), $snapshot['structures'][0]['id']);
        $this->assertSame($structure->getExternalId(), $snapshot['structures'][0]['externalId']);
        $this->assertSame($structure->getName(), $snapshot['structures'][0]['name']);
    }

    public function testLogCreatedPersistsRow()
    {
        $admin  = $this->fixtures->createRawUser('audit_log_actor@test.com', 'password', true);
        $target = $this->fixtures->createRawUser('audit_log_target@test.com', 'password', false);

        $log = $this->manager->logCreated($admin, null, $target);

        $this->assertInstanceOf(UserAuditLog::class, $log);
        $this->assertNotNull($log->getId());
        $this->assertSame('create', $log->getAction());
        $this->assertSame($admin->getId(), $log->getActor() ? $log->getActor()->getId() : null);
        $this->assertSame('audit_log_target@test.com', $log->getTargetUsername());
    }

    public function testLogUpdatedNoOpsWhenSnapshotIdentical()
    {
        $admin  = $this->fixtures->createRawUser('audit_noop_actor@test.com', 'password', true);
        $target = $this->fixtures->createRawUser('audit_noop_target@test.com', 'password', false);

        $snapshot = $this->manager->buildSnapshot($target);

        $result = $this->manager->logUpdated($admin, null, $target, $snapshot);

        $this->assertNull($result, 'logUpdated must return null when nothing changed');
        $this->em->clear();
        $logs = $this->em->getRepository(UserAuditLog::class)->findBy(['targetUsername' => 'audit_noop_target@test.com']);
        $this->assertCount(0, $logs);
    }

    public function testLogUpdatedWritesWhenSnapshotDiffers()
    {
        $admin  = $this->fixtures->createRawUser('audit_diff_actor@test.com', 'password', true);
        $target = $this->fixtures->createRawUser('audit_diff_target@test.com', 'password', false);

        $old = $this->manager->buildSnapshot($target);
        $target->setIsAdmin(true);
        $this->em->persist($target);
        $this->em->flush();

        $log = $this->manager->logUpdated($admin, null, $target, $old);

        $this->assertNotNull($log);
        $snapshot = $log->getSnapshot();
        $this->assertFalse($snapshot['old']['isAdmin']);
        $this->assertTrue($snapshot['new']['isAdmin']);
    }

    public function testLogDeletedKeepsDenormalisedTargetInfo()
    {
        $admin  = $this->fixtures->createRawUser('audit_del_actor@test.com', 'password', true);
        $target = $this->fixtures->createRawUser('audit_delsnap_target@test.com', 'password', false);

        $snapshot = $this->manager->buildSnapshot($target);

        $log = $this->manager->logDeleted($admin, null, $snapshot);

        $this->assertSame('delete', $log->getAction());
        $this->assertNull($log->getTargetUser());
        $this->assertSame('audit_delsnap_target@test.com', $log->getTargetUsername());
        $this->assertSame($snapshot['displayName'], $log->getTargetDisplayName());
    }

    public function testResolveActorLabelFallsBackToCliLabelThenSystem()
    {
        $admin = $this->fixtures->createRawUser('audit_actorlabel@test.com', 'password', true);

        $withActor = $this->manager->logDeleted($admin, null, ['username' => 'x@test.com', 'displayName' => 'X']);
        $this->assertSame($admin->getDisplayName(), $withActor->getActorLabel());

        $withCli = $this->manager->logDeleted(null, 'CLI: user:cron', ['username' => 'y@test.com', 'displayName' => 'Y']);
        $this->assertSame('CLI: user:cron', $withCli->getActorLabel());

        $system = $this->manager->logDeleted(null, null, ['username' => 'z@test.com', 'displayName' => 'Z']);
        $this->assertSame('system', $system->getActorLabel());
    }
}

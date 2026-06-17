<?php

namespace App\Tests\Manager;

use App\Entity\DeletedVolunteer;
use App\Manager\DeletedVolunteerManager;
use App\Repository\DeletedVolunteerRepository;
use App\Tests\Fixtures\DataFixtures;
use App\Tools\Hash;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class DeletedVolunteerManagerTest extends KernelTestCase
{
    private DeletedVolunteerManager $manager;
    private DeletedVolunteerRepository $repository;
    private DataFixtures $fixtures;

    protected function setUp() : void
    {
        self::bootKernel();

        $container        = static::getContainer();
        $this->manager    = $container->get(DeletedVolunteerManager::class);
        $this->repository = $container->get('doctrine')->getRepository(DeletedVolunteer::class);
        $this->fixtures   = new DataFixtures(
            $container->get('doctrine.orm.entity_manager'),
            $container->get('security.password_hasher')
        );
    }

    public function testIsDeletedReturnsFalseWhenNotDeleted()
    {
        $result = $this->manager->isDeleted('NEVER_DELETED_123');

        $this->assertFalse($result);
    }

    public function testIsDeletedReturnsTrueForExactMatch()
    {
        $externalId = 'DEL_TEST_001';

        $deleted = new DeletedVolunteer();
        $deleted->setHashedExternalId(Hash::hash($externalId));
        $this->repository->add($deleted);

        $result = $this->manager->isDeleted($externalId);

        $this->assertTrue($result);
    }

    public function testIsDeletedReturnsTrueForPaddedMatch()
    {
        // Store the hash of the padded version
        $externalId = '123456';
        $paddedId   = str_pad($externalId, 12, '0', STR_PAD_LEFT);

        $deleted = new DeletedVolunteer();
        $deleted->setHashedExternalId(Hash::hash($paddedId));
        $this->repository->add($deleted);

        $result = $this->manager->isDeleted($externalId);

        $this->assertTrue($result);
    }

    public function testUndeleteRemovesDeletedEntry()
    {
        $externalId = 'UNDEL_TEST_001';

        $deleted = new DeletedVolunteer();
        $deleted->setHashedExternalId(Hash::hash($externalId));
        $this->repository->add($deleted);

        // Verify it exists
        $this->assertNotNull(
            $this->repository->findOneByHashedExternalId(Hash::hash($externalId))
        );

        $this->manager->undelete($externalId);

        // Verify it was removed
        $this->assertNull(
            $this->repository->findOneByHashedExternalId(Hash::hash($externalId))
        );
    }

    public function testUndeleteDoesNothingWhenNotDeleted()
    {
        // Should not throw any exception
        $this->manager->undelete('NEVER_EXISTED_999');

        $this->assertTrue(true); // No exception means success
    }

    public function testAnonymizeMarksVolunteerAsDeletedAndChangesExternalId()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'anonymize_test@example.com',
            false,
            'ANON_VOL_001',
            'ANON STRUCT 1',
            'ANON-EXT-001'
        );
        $volunteer = $setup['volunteer'];

        $originalExternalId = $volunteer->getExternalId();

        $this->manager->markGdprDeleted($volunteer);

        // The volunteer's external ID should be hashed in the deleted table
        $deletedEntry = $this->repository->findOneByHashedExternalId(
            Hash::hash($originalExternalId)
        );
        $this->assertNotNull($deletedEntry);

        // The volunteer's external ID should have been changed
        $this->assertStringStartsWith('deleted-', $volunteer->getExternalId());
        $this->assertNotSame($originalExternalId, $volunteer->getExternalId());
    }

    public function testAnonymizeDoesNotDuplicateDeletedEntry()
    {
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'anonymize_dup@example.com',
            false,
            'ANON_VOL_002',
            'ANON STRUCT 2',
            'ANON-EXT-002'
        );
        $volunteer = $setup['volunteer'];

        $originalExternalId = $volunteer->getExternalId();

        // Pre-create a deleted entry
        $deleted = new DeletedVolunteer();
        $deleted->setHashedExternalId(Hash::hash($originalExternalId));
        $this->repository->add($deleted);

        // Anonymize should still work without creating a duplicate
        $this->manager->markGdprDeleted($volunteer);

        $this->assertStringStartsWith('deleted-', $volunteer->getExternalId());
    }

    public function testReleaseExternalIdRenamesButDoesNotPolluteGdprRegistry()
    {
        // Sync-driven anonymize (operational drop, not a legal erase request)
        // must release the original NIVOL so the same person re-imports cleanly
        // next time, but it must NOT mark the NIVOL as GDPR-deleted — otherwise
        // the admin "undelete" UI fills with thousands of false entries every
        // time a Pegass volunteer falls off the export for a week.
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'release_test@example.com',
            false,
            'REL_VOL_001',
            'REL STRUCT 1',
            'REL-EXT-001'
        );
        $volunteer          = $setup['volunteer'];
        $originalExternalId = $volunteer->getExternalId();

        $this->manager->releaseExternalId($volunteer);

        $this->assertStringStartsWith('deleted-', $volunteer->getExternalId());
        $this->assertNotSame($originalExternalId, $volunteer->getExternalId());

        $this->assertNull(
            $this->repository->findOneByHashedExternalId(Hash::hash($originalExternalId)),
            'releaseExternalId must NOT write to the GDPR registry'
        );
        $this->assertFalse($this->manager->isDeleted($originalExternalId),
            'The NIVOL must remain re-importable after releaseExternalId'
        );
    }

    public function testSyncDrivenAnonymizeDoesNotPolluteGdprRegistry()
    {
        // End-to-end: VolunteerManager::anonymize($vol, null, 'sync: stale')
        // must use the release path, not the GDPR-mark path.
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'sync_stale_anon@example.com',
            false,
            'SYNC_STALE_001',
            'SYNC STR 1',
            'SYNC-EXT-1'
        );
        $volunteer          = $setup['volunteer'];
        $originalExternalId = $volunteer->getExternalId();

        $volunteerManager = self::getContainer()->get(\App\Manager\VolunteerManager::class);
        $volunteerManager->anonymize($volunteer, null, 'sync: stale');

        $this->assertFalse($this->manager->isDeleted($originalExternalId),
            'sync-stale anonymize must not flag the NIVOL as GDPR-deleted'
        );
    }

    public function testAdminManualAnonymizeFlagsNivolAsGdprDeleted()
    {
        // A human (admin or volunteer themselves via /space) DID ask for
        // erasure — the NIVOL must land in the GDPR registry so the admin
        // "undelete" workflow can find and restore it later if needed.
        $setup = $this->fixtures->createUserWithVolunteerAndStructure(
            'admin_manual_anon@example.com',
            false,
            'ADMIN_MANUAL_001',
            'AM STR 1',
            'AM-EXT-1'
        );
        $volunteer          = $setup['volunteer'];
        $originalExternalId = $volunteer->getExternalId();

        $volunteerManager = self::getContainer()->get(\App\Manager\VolunteerManager::class);
        $volunteerManager->anonymize($volunteer, null, 'admin: manual');

        $this->assertTrue($this->manager->isDeleted($originalExternalId),
            'admin-driven anonymize must flag the NIVOL as GDPR-deleted'
        );
    }
}

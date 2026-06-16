<?php

namespace App\Tests\Sync;

use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Manager\StructureManager;
use App\Manager\VolunteerManager;
use App\Sync\DataSyncOrchestrator;
use App\Task\FinalizeDataSyncTask;
use App\Task\SyncStructuresChunkTask;
use App\Task\SyncVolunteersChunkTask;
use App\Tests\Fixtures\DataFixtures;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Bundles\SandboxBundle\Service\NullTaskSender;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * End-to-end test of the daily sync against the committed
 * tests/Fixtures/sync/ CSV bundle.
 *
 * TaskSender is replaced by NullTaskSender in the test env, so the chunk
 * dispatches are captured rather than executed. After calling start(),
 * this test replays each captured task by calling the corresponding
 * orchestrator method directly — mirroring what GCT would do in prod.
 */
class DataSyncOrchestratorTest extends KernelTestCase
{
    private DataSyncOrchestrator $orchestrator;
    private NullTaskSender $taskSender;
    private VolunteerManager $volunteerManager;
    private StructureManager $structureManager;
    private \Doctrine\ORM\EntityManagerInterface $em;
    private DataFixtures $fixtures;

    protected function setUp() : void
    {
        self::bootKernel();

        $this->orchestrator     = self::getContainer()->get(DataSyncOrchestrator::class);
        $this->volunteerManager = self::getContainer()->get(VolunteerManager::class);
        $this->structureManager = self::getContainer()->get(StructureManager::class);
        $this->em               = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->fixtures         = new DataFixtures(
            $this->em,
            self::getContainer()->get('security.password_hasher')
        );

        $sender = self::getContainer()->get(TaskSender::class);
        $this->assertInstanceOf(NullTaskSender::class, $sender, 'Test env must use NullTaskSender');
        $this->taskSender = $sender;
    }

    private function runFullSync(\DateTimeImmutable $syncedAt) : void
    {
        $this->orchestrator->start($syncedAt);

        // Replay every dispatched task by calling the orchestrator's chunk
        // methods directly. We have to clone+empty the queue between phases
        // because each call may itself dispatch more tasks.
        foreach ($this->taskSender->getDispatched() as $dispatch) {
            $context = $dispatch['context'];
            switch ($dispatch['name']) {
                case SyncStructuresChunkTask::class:
                    $this->orchestrator->importStructureChunk(
                        $context['rows'] ?? [],
                        new \DateTimeImmutable($context['syncedAt'])
                    );
                    break;
                case SyncVolunteersChunkTask::class:
                    $this->orchestrator->importVolunteerChunk(
                        $context['rows'] ?? [],
                        new \DateTimeImmutable($context['syncedAt'])
                    );
                    break;
                case FinalizeDataSyncTask::class:
                    $this->orchestrator->finalize(new \DateTimeImmutable($context['syncedAt']));
                    break;
            }
        }
    }

    public function testStructuresAreImportedWithParentChain()
    {
        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $ul980 = $this->structureManager->findOneByExternalId('980');
        $dt    = $this->structureManager->findOneByExternalId('80');
        $root  = $this->structureManager->findOneByExternalId('1');

        $this->assertNotNull($ul980);
        $this->assertNotNull($dt);
        $this->assertNotNull($root);
        $this->assertSame('UL EXEMPLE A', $ul980->getName());
        $this->assertSame('80', $ul980->getParentStructure()->getExternalId());
        $this->assertSame('1', $dt->getParentStructure()->getExternalId());
    }

    public function testVolunteerWithValidPse2GetsTrainingBadge()
    {
        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('T0000000001B');
        $this->assertNotNull($volunteer);

        $byExternalId = [];
        foreach ($volunteer->getBadges(false) as $badge) {
            $byExternalId[$badge->getExternalId()] = $badge;
        }
        $this->assertArrayHasKey('training-167', $byExternalId, 'PSE2 (valid until 2027) must be persisted as training-167');

        // The orchestrator's precreateBadges() phase upserts every training
        // badge with the latest dateRecyclage seen across the run before any
        // chunk runs. The fixture has PSE2_OK with expiresAt 2027-12-31.
        $pse2 = $byExternalId['training-167'];
        $this->assertSame('PSE2', $pse2->getName());
        $this->assertSame('2027-12-31', $pse2->getExpiresAt()->format('Y-m-d'));
    }

    public function testVolunteerWithExpiredTrainingDoesNotGetBadge()
    {
        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('T0000000002C');
        $this->assertNotNull($volunteer);

        $externalIds = array_map(fn ($b) => $b->getExternalId(), $volunteer->getBadges(false)->toArray());
        $this->assertNotContains('training-167', $externalIds, 'PSE2 expired in 2020 must not result in a badge');
    }

    public function testMinorVolunteerHasMinorFlag()
    {
        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('T0000000003D');
        $this->assertNotNull($volunteer);
        $this->assertTrue($volunteer->isMinor());
    }

    public function testMultiStructureVolunteerHasBothStructures()
    {
        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('T0000000005F');
        $this->assertNotNull($volunteer);

        $externalIds = array_map(fn ($s) => $s->getExternalId(), $volunteer->getStructures(false)->toArray());
        $this->assertContains('980', $externalIds);
        $this->assertContains('981', $externalIds);
    }

    public function testVolunteerWithNoMailFallsBackToOrganizationEmail()
    {
        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('T0000000007H');
        $this->assertNotNull($volunteer);
        $this->assertNotEmpty($volunteer->getEmail());
        $this->assertSame($volunteer->getInternalEmail(), $volunteer->getEmail(), 'When MAIL is empty, MAILTRAV should be the fallback');
    }

    public function testNominatedVolunteerHasNominationBadge()
    {
        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $volunteer = $this->volunteerManager->findOneByExternalId('T0000000004E');
        $this->assertNotNull($volunteer);

        $externalIds = array_map(fn ($b) => $b->getExternalId(), $volunteer->getBadges(false)->toArray());
        $this->assertContains('nomination-533', $externalIds);
    }

    public function testStructuresMissingFromCsvAreDisabled()
    {
        // Pre-create a structure NOT in the fixture set
        $stale = $this->fixtures->createStructure('STALE STRUCT', '99999');
        $stale->setEnabled(true);
        $stale->setLastSyncedAt(new \DateTime('2020-01-01'));
        $this->em->persist($stale);
        $this->em->flush();

        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $reloaded = $this->structureManager->findOneByExternalId('99999');
        $this->assertNotNull($reloaded);
        $this->assertFalse($reloaded->isEnabled(), 'Structure not in CSV must be disabled by Finalize');
    }

    public function testVolunteersMissingFromCsvAreAnonymized()
    {
        // Pre-create a volunteer NOT in the fixture set
        $stale = $this->fixtures->createStandaloneVolunteer('99999X', 'stale@example.test');
        $stale->setFirstName('Stale');
        $stale->setLastName('PERSON');
        $stale->setEnabled(true);
        $stale->setLastSyncedAt(new \DateTime('2020-01-01'));
        $this->em->persist($stale);
        $this->em->flush();
        $staleId = $stale->getId();

        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $reloaded = $this->em->find(Volunteer::class, $staleId);
        $this->assertNotNull($reloaded);
        $this->assertFalse($reloaded->isEnabled(), 'Volunteer not in CSV must be anonymized/disabled by Finalize');
    }

    public function testLockedUserShieldsItsVolunteerFromAnonymization()
    {
        // Volunteer is Pegass-managed, stale, and would normally match the
        // anonymize sweep. But its bound user is admin-locked — sync must
        // keep its hands off. 84 prod deletions on 2026-06-10/13 bypassed
        // this contract before the fix.
        $structure = $this->fixtures->createStructure('UL TEST', '980');
        $this->em->persist($structure);

        $stale = $this->fixtures->createStandaloneVolunteer('1234567A', 'stale-locked@example.test');
        $stale->setFirstName('Jean');
        $stale->setLastName('Test');
        $stale->setEnabled(true);
        $stale->setLastSyncedAt(new \DateTime('2020-01-01'));
        $this->em->persist($stale);

        $user = $this->fixtures->createRawUser('stale-locked@example.test', 'password', false);
        $user->setVolunteer($stale);
        $user->setIsTrusted(true);
        $user->setLocked(true); // ← admin-locked
        $this->em->persist($user);

        $this->em->flush();
        $volId   = $stale->getId();
        $userId  = $user->getId();

        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $reloadedV = $this->em->find(Volunteer::class, $volId);
        $reloadedU = $this->em->getRepository(\App\Entity\User::class)->find($userId);

        $this->assertTrue($reloadedV->isEnabled(), 'Locked-user volunteer must NOT be anonymized');
        $this->assertNotNull($reloadedU, 'Locked user must survive');
        $this->assertTrue($reloadedU->isTrusted(), 'Locked user must keep trust');
    }

    public function testAnnuaireVolunteersAreNotAnonymizedByPegassSync()
    {
        // Regression: the Pegass sync used to anonymize volunteers created
        // by the Annuaire National flow because their synthetic external_id
        // (user-annu-*, annuaire-*) was never updated by sync:data — their
        // lastSyncedAt stayed old → Finalize saw "stale" → hard-removed the
        // bound RedCall user. See App\Sync\Ownership.
        $annuUser = $this->fixtures->createStandaloneVolunteer('user-annu-jean-test', 'annu1@example.test');
        $annuUser->setFirstName('Jean');
        $annuUser->setLastName('Annu');
        $annuUser->setEnabled(true);
        $annuUser->setLastSyncedAt(new \DateTime('2020-01-01'));
        $this->em->persist($annuUser);

        $annuVol = $this->fixtures->createStandaloneVolunteer('annuaire-abc123def', 'annu2@example.test');
        $annuVol->setFirstName('Marie');
        $annuVol->setLastName('Annu');
        $annuVol->setEnabled(true);
        $annuVol->setLastSyncedAt(new \DateTime('2020-01-01'));
        $this->em->persist($annuVol);

        $this->em->flush();
        $annuUserId = $annuUser->getId();
        $annuVolId  = $annuVol->getId();

        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $reloadedUserAnnu = $this->em->find(Volunteer::class, $annuUserId);
        $reloadedAnnu     = $this->em->find(Volunteer::class, $annuVolId);

        $this->assertTrue($reloadedUserAnnu->isEnabled(), 'user-annu- volunteers must NOT be anonymized by the Pegass sync');
        $this->assertTrue($reloadedAnnu->isEnabled(), 'annuaire- volunteers must NOT be anonymized by the Pegass sync');
    }

    public function testNonNumericStructureIsNotDisabledByPegassSync()
    {
        // The ANNUAIRE NATIONAL / demo structures use UUID external_ids
        // and are not in the Pegass CSV. The Pegass sync must leave them
        // alone even when their lastSyncedAt is ancient.
        $annu = $this->fixtures->createStructure('ANNUAIRE NATIONAL', '792319a4-2e05-4509-bc61-a407d4b70e23');
        $annu->setEnabled(true);
        $annu->setLastSyncedAt(new \DateTime('2020-01-01'));
        $this->em->persist($annu);
        $this->em->flush();

        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $reloaded = $this->structureManager->findOneByExternalId('792319a4-2e05-4509-bc61-a407d4b70e23');
        $this->assertNotNull($reloaded);
        $this->assertTrue($reloaded->isEnabled(), 'UUID-style structure (non-Pegass) must survive Finalize');
    }

    public function testVolunteerWithinGraceWindowSurvivesEvenIfMissingFromCsv()
    {
        // The race between chunks (sync-chunk, 30 concurrent) and finalize
        // (sync-finalize, fires immediately on its own queue) used to wrongly
        // anonymize volunteers whose chunks hadn't drained yet. On 2026-06-14
        // this hit 591 active Pegass volunteers in one night. The grace window
        // turns "stale" from "missed this run" into "missed every daily run
        // for STALE_GRACE_DAYS straight" — wide enough to absorb the race
        // and one-day CSV hiccups, narrow enough to still catch real departures.
        $vol = $this->fixtures->createStandaloneVolunteer('GRACE-1', 'grace@example.test');
        $vol->setFirstName('Grace');
        $vol->setLastName('Recent');
        $vol->setEnabled(true);
        // Yesterday — well inside the 7-day grace window
        $vol->setLastSyncedAt(new \DateTime('-1 day'));
        $this->em->persist($vol);
        $this->em->flush();
        $volId = $vol->getId();

        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $reloaded = $this->em->find(Volunteer::class, $volId);
        $this->assertTrue($reloaded->isEnabled(),
            'Volunteer last synced yesterday must NOT be anonymized — they are within the grace window'
        );
        $this->assertSame('GRACE-1', $reloaded->getExternalId(),
            'External id must be preserved (no rename to deleted-*)'
        );
    }

    public function testStructureWithinGraceWindowSurvivesEvenIfMissingFromCsv()
    {
        $struct = $this->fixtures->createStructure('RECENT STRUCT', '77777');
        $struct->setEnabled(true);
        $struct->setLastSyncedAt(new \DateTime('-2 days'));
        $this->em->persist($struct);
        $this->em->flush();

        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $reloaded = $this->structureManager->findOneByExternalId('77777');
        $this->assertTrue($reloaded->isEnabled(),
            'Structure last synced 2 days ago must NOT be disabled — within the grace window'
        );
    }

    public function testLockedStructureIsNotDisabledEvenIfMissingFromCsv()
    {
        // Pre-create a LOCKED structure NOT in the fixture set
        $locked = $this->fixtures->createStructure('LOCKED', '88888');
        $locked->setLocked(true);
        $locked->setEnabled(true);
        $locked->setLastSyncedAt(new \DateTime('2020-01-01'));
        $this->em->persist($locked);
        $this->em->flush();

        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $reloaded = $this->structureManager->findOneByExternalId('88888');
        $this->assertTrue($reloaded->isEnabled(), 'Locked structures must survive a Finalize sweep');
    }

    public function testTotalVolunteerCountMatchesFixtureSize()
    {
        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        // 20 fixtures volunteers should now exist (created or updated)
        $count = (int) $this->em->createQueryBuilder()
                                ->select('COUNT(v)')
                                ->from(Volunteer::class, 'v')
                                ->where('v.externalId LIKE :prefix')
                                ->setParameter('prefix', 'T%')
                                ->getQuery()
                                ->getSingleScalarResult();

        $this->assertSame(20, $count);
    }

    public function testSnapshotsArePersistedThroughTheChunkPath()
    {
        // Regression: a missing snapshotWriter->flush() at the end of
        // importVolunteerChunk (the GCT path) silently dropped every
        // snapshot in production. Make sure the chunk path actually
        // commits the buffered upserts.
        $this->runFullSync(new \DateTimeImmutable());
        $this->em->clear();

        $count = (int) $this->em->getConnection()
            ->executeQuery('SELECT COUNT(*) FROM volunteer_sync_snapshot')
            ->fetchOne();

        // Locked + missing-name volunteers don't get a snapshot, but the
        // 20 fixture volunteers all have valid data so every one of them
        // should have left a row behind.
        $this->assertSame(20, $count);
    }
}

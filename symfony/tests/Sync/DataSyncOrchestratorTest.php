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

        $externalIds = array_map(fn ($b) => $b->getExternalId(), $volunteer->getBadges(false)->toArray());
        $this->assertContains('training-167', $externalIds, 'PSE2 (valid until 2027) must be persisted as training-167');
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
}

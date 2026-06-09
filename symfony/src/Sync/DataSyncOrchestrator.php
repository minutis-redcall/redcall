<?php

namespace App\Sync;

use App\Entity\Structure;
use App\Entity\Volunteer;
use App\Manager\StructureManager;
use App\Manager\VolunteerManager;
use App\Queues;
use App\Sync\Dto\ActionRow;
use App\Sync\Dto\NominationRow;
use App\Sync\Dto\SkillRow;
use App\Sync\Dto\StructureRow;
use App\Sync\Dto\TrainingRow;
use App\Sync\Dto\VolunteerRow;
use App\Sync\Importer\BadgeFactory;
use App\Sync\Importer\StructureImporter;
use App\Sync\Importer\VolunteerImporter;
use App\Sync\Reader\CsvReader;
use App\Sync\Reconciliation\RtmrReconciliator;
use App\Sync\Reference\ReferenceTables;
use App\Sync\Reporter\NullSyncProgressReporter;
use App\Sync\Reporter\SyncProgressReporter;
use App\Sync\Source\CsvSourceInterface;
use App\Sync\Writer\VolunteerSyncSnapshotWriter;
use App\Task\FinalizeDataSyncTask;
use App\Task\SyncStructuresChunkTask;
use App\Task\SyncVolunteersChunkTask;
use Bundles\GoogleTaskBundle\Service\TaskSender;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

/**
 * Coordinates the daily CSV-based sync of volunteers and structures.
 *
 * Step 1 (StartDataSyncTask):
 *   downloads CSVs, loads reference tables, builds typed DTOs, dispatches
 *   chunked Sync*ChunkTasks and a final FinalizeDataSyncTask.
 *
 * Step 2 (chunk tasks):
 *   delegate per-row imports to StructureImporter / VolunteerImporter.
 *
 * Step 3 (FinalizeDataSyncTask):
 *   disables structures and anonymizes volunteers whose lastSyncedAt is
 *   older than the sync run's start timestamp, re-applies parent linking, and
 *   runs the RTMR reconciliation in batch.
 */
class DataSyncOrchestrator
{
    public const STRUCTURE_CHUNK_SIZE = 50;
    public const VOLUNTEER_CHUNK_SIZE = 50;
    public const MIN_CSV_FILES        = 10;

    private CsvSourceInterface $source;
    private CsvReader $csvReader;
    private ReferenceTables $referenceTables;
    private StructureImporter $structureImporter;
    private VolunteerImporter $volunteerImporter;
    private BadgeFactory $badgeFactory;
    private RtmrReconciliator $rtmrReconciliator;
    private StructureManager $structureManager;
    private VolunteerManager $volunteerManager;
    private EntityManagerInterface $em;
    private TaskSender $async;
    private VolunteerSyncSnapshotWriter $snapshotWriter;
    private LoggerInterface $logger;
    private SyncProgressReporter $progress;
    private ?DebugDataHolder $debugDataHolder = null;

    public function __construct(
        CsvSourceInterface $source,
        CsvReader $csvReader,
        ReferenceTables $referenceTables,
        StructureImporter $structureImporter,
        VolunteerImporter $volunteerImporter,
        BadgeFactory $badgeFactory,
        RtmrReconciliator $rtmrReconciliator,
        StructureManager $structureManager,
        VolunteerManager $volunteerManager,
        EntityManagerInterface $em,
        TaskSender $async,
        VolunteerSyncSnapshotWriter $snapshotWriter,
        ?LoggerInterface $logger = null
    ) {
        $this->source            = $source;
        $this->csvReader         = $csvReader;
        $this->referenceTables   = $referenceTables;
        $this->structureImporter = $structureImporter;
        $this->volunteerImporter = $volunteerImporter;
        $this->badgeFactory      = $badgeFactory;
        $this->rtmrReconciliator = $rtmrReconciliator;
        $this->structureManager  = $structureManager;
        $this->volunteerManager  = $volunteerManager;
        $this->em                = $em;
        $this->async             = $async;
        $this->snapshotWriter    = $snapshotWriter;
        $this->logger            = $logger ?? new NullLogger();
        $this->progress          = new NullSyncProgressReporter();
    }

    public function setLogger(LoggerInterface $logger) : void
    {
        $this->logger = $logger;
    }

    public function setProgressReporter(SyncProgressReporter $reporter) : void
    {
        $this->progress = $reporter;
    }

    /**
     * Allow the CLI dry-run to plug in Doctrine's per-query DebugDataHolder
     * (dev env only). The orchestrator will reset() it after every chunk to
     * keep the in-memory query log from accumulating across the whole run.
     */
    public function setDoctrineDebugDataHolder(?DebugDataHolder $holder) : void
    {
        $this->debugDataHolder = $holder;
    }

    /**
     * Standard sync run: downloads, collects, and dispatches chunked tasks
     * to Google Cloud Tasks (which inline-execute in dev). This is what
     * StartDataSyncTask invokes on the daily cron.
     */
    public function start(\DateTimeImmutable $syncedAt) : void
    {
        $this->run($syncedAt, $this->source, dispatchAsync: true);
    }

    /**
     * One-shot inline run from an explicit source (typically LocalCsvSource).
     * Bypasses TaskSender and applies every chunk in-process. Used by
     * `sync:data --dir=/path/to/csvs` for local dry-runs.
     */
    public function runInline(\DateTimeImmutable $syncedAt, CsvSourceInterface $source) : void
    {
        $this->run($syncedAt, $source, dispatchAsync: false);
    }

    private function run(\DateTimeImmutable $syncedAt, CsvSourceInterface $source, bool $dispatchAsync) : void
    {
        $this->progress->info('Downloading CSV files...');
        $files = $source->download();
        $this->logger->info(sprintf('Downloaded %d CSV files', count($files)));
        $this->progress->info(sprintf('Downloaded %d CSV files', count($files)));

        if (count($files) < self::MIN_CSV_FILES) {
            $this->logger->critical(sprintf(
                'Sync aborted: incomplete CSV export (%d files, expected at least %d)',
                count($files),
                self::MIN_CSV_FILES
            ), ['files' => array_keys($files)]);

            return;
        }

        $this->progress->info('Loading reference tables...');
        $this->referenceTables->load($files);

        $structureRows = $this->collectStructures($files);
        $this->logger->info(sprintf('Collected %d structures', count($structureRows)));
        $this->progress->info(sprintf('Collected %d structures', count($structureRows)));

        $volunteerRows = $this->collectVolunteers($files);
        $this->logger->info(sprintf('Collected %d volunteers', count($volunteerRows)));
        $this->progress->info(sprintf('Collected %d volunteers', count($volunteerRows)));

        // Pre-create every badge referenced by any volunteer row, in a single
        // serialized step. Chunk tasks (which run with 30× concurrency on the
        // sync-chunk queue) only READ badges afterwards — no concurrent UPDATE
        // on the shared `badge` rows, no more deadlocks.
        $this->precreateBadges($volunteerRows);

        if ($dispatchAsync) {
            $this->dispatchStructureChunks($structureRows, $syncedAt);
            $this->dispatchVolunteerChunks($volunteerRows, $syncedAt);
            $this->dispatchFinalize($syncedAt);
        } else {
            // The inline path mutates these arrays as it processes them
            // (array_splice on each chunk) so they shrink to empty and PHP
            // can reclaim the DTOs progressively instead of holding the
            // full 1.2 GB-worth of VolunteerRow data until the end.
            $this->processStructureChunksInline($structureRows, $syncedAt);
            unset($structureRows);
            gc_collect_cycles();

            $this->processVolunteerChunksInline($volunteerRows, $syncedAt);
            unset($volunteerRows);
            gc_collect_cycles();

            $this->finalize($syncedAt);
        }
    }

    /**
     * @param StructureRow[] $rows
     */
    private function processStructureChunksInline(array &$rows, \DateTimeImmutable $syncedAt) : void
    {
        $total = count($rows);
        $this->progress->startBar(sprintf('Importing %d structures', $total), $total);

        $done = 0;
        while (!empty($rows)) {
            $chunk = array_splice($rows, 0, self::STRUCTURE_CHUNK_SIZE);
            foreach ($chunk as $row) {
                $this->structureImporter->import($row, $syncedAt);
            }
            $done += count($chunk);
            $this->resetMemoryFootprint();
            $this->progress->advanceBar(count($chunk));
            $this->logger->info(sprintf('Structures imported: %d / %d', $done, $total));
        }

        $this->progress->finishBar();
    }

    /**
     * @param VolunteerRow[] $rows
     */
    private function processVolunteerChunksInline(array &$rows, \DateTimeImmutable $syncedAt) : void
    {
        $total = count($rows);
        $this->progress->startBar(sprintf('Importing %d volunteers', $total), $total);

        $done = 0;
        while (!empty($rows)) {
            $chunk = array_splice($rows, 0, self::VOLUNTEER_CHUNK_SIZE);
            foreach ($chunk as $row) {
                $this->volunteerImporter->import($row, $syncedAt);
            }
            // One batched INSERT ... ON DUPLICATE KEY UPDATE for the whole
            // chunk instead of 50 × (SELECT + INSERT/UPDATE + EM flush).
            $this->snapshotWriter->flush();
            $done += count($chunk);
            $this->resetMemoryFootprint();
            $this->progress->advanceBar(count($chunk));
            $this->logger->info(sprintf('Volunteers imported: %d / %d', $done, $total));
        }

        $this->progress->finishBar();
    }

    /**
     * Walks every VolunteerRow once and bulk-upserts every badge it references
     * (groupeAction-/skill-/training-/nomination-{id}). For trainings we keep
     * the LATEST dateRecyclage seen across volunteers as the badge's
     * expires_at — that way `Badge::hasExpired()` (used by the locked-volunteer
     * cleanup) is meaningful: the badge is "expired" only when nobody's
     * matching cert is valid anymore.
     *
     * Concurrent chunk tasks would otherwise all race to UPDATE the same ~700
     * shared badge rows and deadlock in production. This pre-step runs once
     * inside StartDataSyncTask (sync-start queue, single-concurrency).
     *
     * @param VolunteerRow[] $volunteerRows
     */
    private function precreateBadges(array $volunteerRows) : void
    {
        $now              = new \DateTimeImmutable();
        $actionBadges     = [];
        $skillBadges      = [];
        $trainingBadges   = [];
        $nominationBadges = [];

        foreach ($volunteerRows as $row) {
            foreach ($row->actions as $action) {
                /** @var ActionRow $action */
                if ('' === $action->groupActionId) {
                    continue;
                }
                $actionBadges['groupeAction-'.$action->groupActionId] = [
                    'externalId'  => 'groupeAction-'.$action->groupActionId,
                    'name'        => $action->groupActionLabel,
                    'description' => $action->groupActionLabel,
                    'expiresAt'   => null,
                ];
            }

            foreach ($row->skills as $skill) {
                /** @var SkillRow $skill */
                if ('' === $skill->competenceId) {
                    continue;
                }
                $skillBadges['skill-'.$skill->competenceId] = [
                    'externalId'  => 'skill-'.$skill->competenceId,
                    'name'        => $skill->label,
                    'description' => $skill->label,
                    'expiresAt'   => null,
                ];
            }

            foreach ($row->trainings as $training) {
                /** @var TrainingRow $training */
                if ('' === $training->formationId) {
                    continue;
                }
                if ($training->expiresAt !== null && $training->expiresAt < $now) {
                    // Don't bother creating a badge for a formation whose
                    // every known training is already expired.
                    continue;
                }
                $key   = 'training-'.$training->formationId;
                $first = !isset($trainingBadges[$key]);
                if ($first) {
                    $expiry = $training->expiresAt;
                } else {
                    $expiry = $this->mergeMaxExpiry(
                        $trainingBadges[$key]['expiresAt'],
                        $training->expiresAt
                    );
                }
                $trainingBadges[$key] = [
                    'externalId'  => $key,
                    'name'        => $training->code,
                    'description' => $training->label,
                    'expiresAt'   => $expiry,
                ];
            }

            foreach ($row->nominations as $nomination) {
                /** @var NominationRow $nomination */
                if ('' === $nomination->nominationId) {
                    continue;
                }
                $nominationBadges['nomination-'.$nomination->nominationId] = [
                    'externalId'  => 'nomination-'.$nomination->nominationId,
                    'name'        => $nomination->code,
                    'description' => $nomination->label,
                    'expiresAt'   => null,
                ];
            }
        }

        $this->badgeFactory->bulkUpsert(array_values($actionBadges));
        $this->badgeFactory->bulkUpsert(array_values($skillBadges));
        $this->badgeFactory->bulkUpsert(array_values($trainingBadges));
        $this->badgeFactory->bulkUpsert(array_values($nominationBadges));

        $count = count($actionBadges) + count($skillBadges) + count($trainingBadges) + count($nominationBadges);
        $this->logger->info(sprintf('Pre-created/refreshed %d badges', $count));
        $this->progress->info(sprintf('Pre-created/refreshed %d badges', $count));
    }

    /**
     * Compose two optional DateTimeImmutable values keeping the *later* one.
     * A null on either side means "no known expiry" — kept null so the badge
     * stays valid forever as long as at least one volunteer has a never-
     * expiring training of that type.
     */
    private function mergeMaxExpiry(?\DateTimeImmutable $a, ?\DateTimeImmutable $b) : ?\DateTimeImmutable
    {
        if ($a === null || $b === null) {
            return null;
        }

        return $a > $b ? $a : $b;
    }

    /**
     * Reset the per-chunk memory footprint:
     *   - em->clear() detaches all managed entities,
     *   - the Doctrine debug data holder (dev env only) is reset so the
     *     per-query SQL log doesn't accumulate to hundreds of MB,
     *   - gc_collect_cycles() forces PHP's cycle collector to actually
     *     reclaim the entities (Volunteer↔Phone, Volunteer↔Badge etc. are
     *     refcount cycles that refcount alone can't break).
     */
    private function resetMemoryFootprint() : void
    {
        $this->em->clear();
        $this->debugDataHolder?->reset();
        gc_collect_cycles();
    }

    /**
     * Apply a structure chunk. Called by SyncStructuresChunkTask.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    public function importStructureChunk(array $rows, \DateTimeImmutable $syncedAt) : void
    {
        foreach ($rows as $data) {
            $this->structureImporter->import(StructureRow::fromArray($data), $syncedAt);
        }
    }

    /**
     * Apply a volunteer chunk. Called by SyncVolunteersChunkTask.
     *
     * @param array<int,array<string,mixed>> $rows
     */
    public function importVolunteerChunk(array $rows, \DateTimeImmutable $syncedAt) : void
    {
        foreach ($rows as $data) {
            $this->volunteerImporter->import(VolunteerRow::fromArray($data), $syncedAt);
        }
    }

    /**
     * Final pass: disable structures and anonymize volunteers that were not
     * touched by this sync run, then re-apply parent linking and reconcile
     * RedCall users via RTMR rules. Called by FinalizeDataSyncTask.
     */
    public function finalize(\DateTimeImmutable $syncedAt) : void
    {
        // Each sub-step opens its own progress bar with the row count it has
        // to process, so we don't pre-announce phases here.
        $this->disableStaleStructures($syncedAt);
        $this->anonymizeStaleVolunteers($syncedAt);
        $this->reconcileRtmr();

        $this->progress->info('Sync complete.');
    }

    /**
     * @return StructureRow[]
     */
    private function collectStructures(array $files) : array
    {
        if (!isset($files['redcall_ref_structures.csv'])) {
            return [];
        }

        $structures = [];
        foreach ($this->csvReader->read($files['redcall_ref_structures.csv']) as $row) {
            $structures[] = StructureRow::fromCsvRow($row);
        }

        return $structures;
    }

    /**
     * @return VolunteerRow[]
     */
    private function collectVolunteers(array $files) : array
    {
        if (!isset($files['redcall_benevoles.csv'])) {
            return [];
        }

        // Step 1 — base info
        $bag         = [];
        $benevoleCsv = $files['redcall_benevoles.csv'];
        $this->progress->startBar('Reading volunteers', $this->csvReader->countRows($benevoleCsv));
        foreach ($this->csvReader->read($benevoleCsv) as $row) {
            // nivol,nom,prenom,age,email,email_crf,telephone,id_structure
            $this->progress->advanceBar();
            $nivol = $row[0];
            if ('' === $nivol) {
                continue;
            }
            $bag[$nivol] = [
                'nivol'             => $nivol,
                'lastName'          => $row[1] ?? '',
                'firstName'         => $row[2] ?? '',
                'age'               => (int) ($row[3] ?? 0),
                'personalEmail'     => $row[4] ?? '',
                'organizationEmail' => $row[5] ?? '',
                'phone'             => $row[6] ?? '',
                'structureId'       => $row[7] ?? '',
                'actions'           => [],
                'trainings'         => [],
                'skills'            => [],
                'nominations'       => [],
            ];
        }
        $this->progress->finishBar();

        // Step 2 — actions
        if (isset($files['redcall_groupes_actions_menees.csv'])) {
            $actionsCsv = $files['redcall_groupes_actions_menees.csv'];
            $this->progress->startBar('Reading actions', $this->csvReader->countRows($actionsCsv));
            foreach ($this->csvReader->read($actionsCsv) as $row) {
                // nivol,id_structure,id_groupe_action
                $this->progress->advanceBar();
                $nivol = $row[0];
                if (!isset($bag[$nivol])) {
                    continue;
                }
                $groupId = $row[2] ?? '';
                if (!$this->referenceTables->hasGroupAction($groupId)) {
                    continue;
                }
                $bag[$nivol]['actions'][] = (new ActionRow(
                    structureId: (string) ($row[1] ?? ''),
                    groupActionId: (string) $groupId,
                    groupActionLabel: (string) $this->referenceTables->getGroupActionLabel($groupId)
                ))->toArray();
            }
            $this->progress->finishBar();
        }

        // Step 3 — skills
        if (isset($files['redcall_competences_acquises.csv'])) {
            $skillsCsv = $files['redcall_competences_acquises.csv'];
            $this->progress->startBar('Reading skills', $this->csvReader->countRows($skillsCsv));
            foreach ($this->csvReader->read($skillsCsv) as $row) {
                // nivol,id_competence
                $this->progress->advanceBar();
                $nivol = $row[0];
                if (!isset($bag[$nivol])) {
                    continue;
                }
                $competenceId = $row[1] ?? '';
                if (!$this->referenceTables->hasCompetence($competenceId)) {
                    continue;
                }
                $bag[$nivol]['skills'][] = (new SkillRow(
                    competenceId: (string) $competenceId,
                    label: (string) $this->referenceTables->getCompetenceLabel($competenceId)
                ))->toArray();
            }
            $this->progress->finishBar();
        }

        // Step 4 — trainings
        if (isset($files['redcall_formes.csv'])) {
            $trainingsCsv = $files['redcall_formes.csv'];
            $this->progress->startBar('Reading trainings', $this->csvReader->countRows($trainingsCsv));
            foreach ($this->csvReader->read($trainingsCsv) as $row) {
                // nivol,id_formation,date_obtention,date_recyclage
                $this->progress->advanceBar();
                $nivol = $row[0];
                if (!isset($bag[$nivol])) {
                    continue;
                }
                $formationId = $row[1] ?? '';
                $formation   = $this->referenceTables->getFormation($formationId);
                if (!$formation) {
                    continue;
                }
                $bag[$nivol]['trainings'][] = (new TrainingRow(
                    formationId: (string) $formationId,
                    code: $formation['code'],
                    label: $formation['label'],
                    gotAt: $this->parseFrenchDate($row[2] ?? ''),
                    expiresAt: $this->parseFrenchDate($row[3] ?? '')
                ))->toArray();
            }
            $this->progress->finishBar();
        }

        // Step 5 — nominations
        if (isset($files['redcall_nommes.csv'])) {
            $nominationsCsv = $files['redcall_nommes.csv'];
            $this->progress->startBar('Reading nominations', $this->csvReader->countRows($nominationsCsv));
            foreach ($this->csvReader->read($nominationsCsv) as $row) {
                // nivol,id_structure,id_nomination,date_validation,date_fin
                $this->progress->advanceBar();
                $nivol = $row[0];
                if (!isset($bag[$nivol])) {
                    continue;
                }
                $nominationId = $row[2] ?? '';
                $nomination   = $this->referenceTables->getNomination($nominationId);
                if (!$nomination) {
                    continue;
                }
                $bag[$nivol]['nominations'][] = (new NominationRow(
                    nominationId: (string) $nominationId,
                    code: $nomination['code'],
                    label: $nomination['label'],
                    structureId: (string) ($row[1] ?? ''),
                    gotAt: $this->parseIsoDate($row[3] ?? '')
                ))->toArray();
            }
            $this->progress->finishBar();
        }

        // Convert raw arrays to VolunteerRow DTOs
        $rows = [];
        foreach ($bag as $data) {
            $rows[] = VolunteerRow::fromArray($data);
        }

        return $rows;
    }

    /**
     * @param StructureRow[] $rows
     */
    private function dispatchStructureChunks(array $rows, \DateTimeImmutable $syncedAt) : void
    {
        if (!$rows) {
            return;
        }

        foreach (array_chunk($rows, self::STRUCTURE_CHUNK_SIZE) as $chunk) {
            $this->async->fire(SyncStructuresChunkTask::class, [
                'syncedAt' => $syncedAt->format(\DateTimeInterface::ATOM),
                'rows'     => array_map(fn (StructureRow $r) => $r->toArray(), $chunk),
            ]);
        }
    }

    /**
     * @param VolunteerRow[] $rows
     */
    private function dispatchVolunteerChunks(array $rows, \DateTimeImmutable $syncedAt) : void
    {
        if (!$rows) {
            return;
        }

        foreach (array_chunk($rows, self::VOLUNTEER_CHUNK_SIZE) as $chunk) {
            $this->async->fire(SyncVolunteersChunkTask::class, [
                'syncedAt' => $syncedAt->format(\DateTimeInterface::ATOM),
                'rows'     => array_map(fn (VolunteerRow $r) => $r->toArray(), $chunk),
            ]);
        }
    }

    private function dispatchFinalize(\DateTimeImmutable $syncedAt) : void
    {
        $this->async->fire(FinalizeDataSyncTask::class, [
            'syncedAt' => $syncedAt->format(\DateTimeInterface::ATOM),
        ]);
    }

    private function disableStaleStructures(\DateTimeImmutable $syncedAt) : void
    {
        // Single UPDATE statement, no per-row work — show the count up-front
        // (so it's visible even though the actual query is near-instant).
        $countQb = $this->em->createQueryBuilder()
                            ->select('COUNT(s.id)')
                            ->from(Structure::class, 's')
                            ->where('s.locked = :unlocked')
                            ->andWhere('s.enabled = :enabled')
                            ->andWhere('(s.lastSyncedAt IS NULL OR s.lastSyncedAt < :syncedAt)')
                            ->setParameter('enabled', true)
                            ->setParameter('unlocked', false)
                            ->setParameter('syncedAt', \DateTime::createFromImmutable($syncedAt));
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $this->progress->startBar(sprintf('Disabling %d stale structures', $total), max(1, $total));

        $qb = $this->em->createQueryBuilder()
                       ->update(Structure::class, 's')
                       ->set('s.enabled', ':disabled')
                       ->where('s.locked = :unlocked')
                       ->andWhere('s.enabled = :enabled')
                       ->andWhere('(s.lastSyncedAt IS NULL OR s.lastSyncedAt < :syncedAt)')
                       ->setParameter('disabled', false)
                       ->setParameter('enabled', true)
                       ->setParameter('unlocked', false)
                       ->setParameter('syncedAt', \DateTime::createFromImmutable($syncedAt));

        $count = $qb->getQuery()->execute();
        $this->progress->advanceBar(max(1, (int) $count));
        $this->progress->finishBar();
        $this->logger->info(sprintf('Disabled %d stale structures', $count));
    }

    private function anonymizeStaleVolunteers(\DateTimeImmutable $syncedAt) : void
    {
        $countQb = $this->em->createQueryBuilder()
                            ->select('COUNT(v.id)')
                            ->from(Volunteer::class, 'v')
                            ->where('v.locked = :unlocked')
                            ->andWhere('v.enabled = :enabled')
                            ->andWhere('(v.lastSyncedAt IS NULL OR v.lastSyncedAt < :syncedAt)')
                            ->setParameter('enabled', true)
                            ->setParameter('unlocked', false)
                            ->setParameter('syncedAt', \DateTime::createFromImmutable($syncedAt));
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $this->progress->startBar(sprintf('Anonymizing %d stale volunteers', $total), max(1, $total));

        $qb = $this->em->createQueryBuilder()
                       ->select('v')
                       ->from(Volunteer::class, 'v')
                       ->where('v.locked = :unlocked')
                       ->andWhere('v.enabled = :enabled')
                       ->andWhere('(v.lastSyncedAt IS NULL OR v.lastSyncedAt < :syncedAt)')
                       ->setParameter('enabled', true)
                       ->setParameter('unlocked', false)
                       ->setParameter('syncedAt', \DateTime::createFromImmutable($syncedAt));

        $count = 0;
        foreach ($qb->getQuery()->toIterable() as $volunteer) {
            /** @var Volunteer $volunteer */
            $this->volunteerManager->anonymize($volunteer);
            $count++;
            $this->progress->advanceBar();
            if (0 === $count % self::VOLUNTEER_CHUNK_SIZE) {
                $this->resetMemoryFootprint();
            }
        }

        if (0 === $total) {
            $this->progress->advanceBar();
        }
        $this->progress->finishBar();
        $this->resetMemoryFootprint();
        $this->logger->info(sprintf('Anonymized %d stale volunteers', $count));
    }

    private function reconcileRtmr() : void
    {
        $countQb = $this->em->createQueryBuilder()
                            ->select('COUNT(DISTINCT v.id)')
                            ->from(Volunteer::class, 'v')
                            ->leftJoin('v.user', 'u')
                            ->leftJoin('v.badges', 'b')
                            ->where('u.id IS NOT NULL')
                            ->orWhere('b.name IN (:rtmrBadges)')
                            ->setParameter('rtmrBadges', [
                                RtmrReconciliator::RTMR_BADGE,
                                RtmrReconciliator::INVALID_RTMR_BADGE,
                            ]);
        $total = (int) $countQb->getQuery()->getSingleScalarResult();

        $this->progress->startBar(sprintf('Reconciling %d RTMR / RedCall users', $total), max(1, $total));

        $qb = $this->em->createQueryBuilder()
                       ->select('DISTINCT v')
                       ->from(Volunteer::class, 'v')
                       ->leftJoin('v.user', 'u')
                       ->leftJoin('v.badges', 'b')
                       ->where('u.id IS NOT NULL')
                       ->orWhere('b.name IN (:rtmrBadges)')
                       ->setParameter('rtmrBadges', [
                           RtmrReconciliator::RTMR_BADGE,
                           RtmrReconciliator::INVALID_RTMR_BADGE,
                       ]);

        $count = 0;
        foreach ($qb->getQuery()->toIterable() as $volunteer) {
            /** @var Volunteer $volunteer */
            $this->rtmrReconciliator->reconcile($volunteer);
            $count++;
            $this->progress->advanceBar();
            if (0 === $count % self::VOLUNTEER_CHUNK_SIZE) {
                $this->resetMemoryFootprint();
            }
        }

        if (0 === $total) {
            $this->progress->advanceBar();
        }
        $this->progress->finishBar();
        $this->resetMemoryFootprint();
        $this->logger->info(sprintf('Reconciled %d volunteer/user pairs (RTMR rules)', $count));
    }

    private function parseFrenchDate(string $raw) : ?\DateTimeImmutable
    {
        if ('' === $raw) {
            return null;
        }
        $d = \DateTimeImmutable::createFromFormat('d/m/Y', $raw);

        return false === $d ? null : $d->setTime(0, 0, 0);
    }

    private function parseIsoDate(string $raw) : ?\DateTimeImmutable
    {
        if ('' === $raw) {
            return null;
        }
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $raw);
        if (false === $d) {
            $d = \DateTimeImmutable::createFromFormat(\DateTimeInterface::ATOM, $raw);
        }

        return false === $d ? null : $d->setTime(0, 0, 0);
    }
}

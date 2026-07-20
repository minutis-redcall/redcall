<?php

namespace App\Sync\Importer;

use App\Entity\Badge;
use App\Manager\BadgeManager;
use Doctrine\DBAL\Connection;

use function Symfony\Component\String\u;

/**
 * Finds or creates Badge entities by external id. External id conventions:
 *   - "groupeAction-{id}" for activity group badges
 *   - "skill-{id}" for competence badges
 *   - "training-{id}" for formation badges
 *   - "nomination-{id}" for nomination badges
 *
 * In production the daily sync runs many chunk tasks concurrently. To avoid
 * the resulting deadlocks on the small set of shared `badge` rows
 * (one PSE2 row touched by every volunteer that holds a PSE2 cert), we
 * upsert all badges once up-front via bulkUpsert() — called by the
 * orchestrator's StartDataSyncTask (single-concurrency) — and the chunk
 * tasks only ever READ badges through findOrCreate(). The create path
 * remains as a safety net for badges that somehow appear in volunteer
 * data without being pre-created.
 */
class BadgeFactory
{
    /**
     * bulkUpsert() aborts when it would rename more than this share of the
     * existing badges it touches: a legitimate run tweaks a few labels, while
     * a massive rename means the upstream referential was renumbered and
     * every existing badge row would silently change meaning while keeping
     * its RedCall config (2026-07-19 incident).
     */
    public const MAX_RENAME_RATIO = 0.10;

    /**
     * Below this many existing rows the ratio is meaningless (a 2-badge
     * sandbox run renaming 1 badge is not an incident).
     */
    private const RENAME_GUARD_MIN_EXISTING = 10;

    private BadgeManager $badgeManager;
    private Connection $conn;

    public function __construct(BadgeManager $badgeManager, Connection $conn)
    {
        $this->badgeManager = $badgeManager;
        $this->conn         = $conn;
    }

    public function findOrCreate(string $externalId, string $name, ?string $description = null) : Badge
    {
        $badge = $this->badgeManager->findOneByExternalId($externalId);
        if ($badge) {
            return $badge;
        }

        if (null === $description) {
            $description = $name;
        }

        $badge = new Badge();
        $badge->setExternalId($externalId);
        $badge->setName(substr($name, 0, 64));
        $badge->setDescription(substr($description, 0, 255));
        $this->badgeManager->save($badge);

        return $badge;
    }

    /**
     * Bulk-upsert badges via raw DBAL. Used once per sync run from
     * StartDataSyncTask to ensure every external id referenced by any
     * volunteer exists with the desired name/description/expires_at —
     * before any chunk task gets a chance to do concurrent writes on
     * those same rows.
     *
     * Each item: ['externalId' => string, 'name' => string,
     *             'description' => string, 'expiresAt' => ?DateTimeImmutable]
     *
     * ON DUPLICATE KEY UPDATE refreshes name/description/expires_at so
     * label tweaks in the DSI reference data propagate, and expirations
     * advance as new training rows arrive.
     *
     * Set $allowMassRename to true only when an upstream referential change
     * is expected and has been reconciled (see the rename guard above).
     *
     * @param array<int,array{externalId:string,name:string,description:string,expiresAt:?\DateTimeImmutable}> $items
     */
    public function bulkUpsert(array $items, bool $allowMassRename = false) : void
    {
        if (!$items) {
            return;
        }

        if (!$allowMassRename) {
            $this->guardAgainstMassRename($items);
        }

        // Chunk into reasonable batch sizes — MySQL has a max_allowed_packet
        // limit, and very wide INSERTs slow down the parser.
        foreach (array_chunk($items, 200) as $batch) {
            // 4 dynamic columns per row, the rest are NOT NULL defaults that
            // match the Badge entity's initializers (rendering_priority=0,
            // triggering_priority=500, visibility=0, enabled=1, locked=0).
            $placeholders = implode(', ', array_fill(0, count($batch), '(?, ?, ?, ?, 0, 500, 0, 1, 0)'));
            $params       = [];
            foreach ($batch as $item) {
                $params[] = $item['externalId'];
                $params[] = substr($item['name'], 0, 64);
                $params[] = substr($item['description'] ?? $item['name'], 0, 255);
                $params[] = $item['expiresAt']?->format('Y-m-d');
            }

            $sql = 'INSERT INTO badge ('
                .'external_id, name, description, expires_at, '
                .'rendering_priority, triggering_priority, visibility, enabled, locked'
                .') VALUES '.$placeholders
                .' ON DUPLICATE KEY UPDATE '
                .'name = VALUES(name), '
                .'description = VALUES(description), '
                .'expires_at = VALUES(expires_at)';

            $this->conn->executeStatement($sql, $params);
        }
    }

    /**
     * @param array<int,array{externalId:string,name:string,description:string,expiresAt:?\DateTimeImmutable}> $items
     */
    private function guardAgainstMassRename(array $items) : void
    {
        $incoming = [];
        foreach ($items as $item) {
            $incoming[$item['externalId']] = substr($item['name'], 0, 64);
        }

        $renamed  = [];
        $existing = 0;
        foreach (array_chunk(array_keys($incoming), 500) as $chunk) {
            $rows = $this->conn->fetchAllAssociative(
                'SELECT external_id, name FROM badge WHERE external_id IN (?)',
                [$chunk],
                [\Doctrine\DBAL\ArrayParameterType::STRING]
            );
            foreach ($rows as $row) {
                $existing++;
                if ($this->normalizeName($row['name']) !== $this->normalizeName($incoming[$row['external_id']])) {
                    $renamed[] = sprintf('%s: "%s" -> "%s"', $row['external_id'], $row['name'], $incoming[$row['external_id']]);
                }
            }
        }

        if ($existing < self::RENAME_GUARD_MIN_EXISTING) {
            return;
        }

        $ratio = count($renamed) / $existing;
        if ($ratio <= self::MAX_RENAME_RATIO) {
            return;
        }

        throw new \RuntimeException(sprintf(
            'Badge sync aborted: this run would rename %d of the %d existing badges it touches (%d%%, max allowed %d%%). '
            .'This usually means the upstream referential was renumbered: existing badge rows would silently change '
            .'meaning while keeping their RedCall configuration. Reconcile the referential first. First renames: %s',
            count($renamed),
            $existing,
            (int) round(100 * $ratio),
            (int) round(100 * self::MAX_RENAME_RATIO),
            implode(' | ', array_slice($renamed, 0, 5))
        ));
    }

    /**
     * A "rename" only counts toward the guard when it changes the badge's
     * meaning: accent or case tweaks in upstream labels (e.g. "Réserve" vs
     * "Reserve" after the 2026-07 referential migration) are cosmetic.
     */
    private function normalizeName(string $name) : string
    {
        return u($name)->ascii()->lower()->trim()->toString();
    }
}

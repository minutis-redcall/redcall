<?php

namespace App\Sync\Importer;

use App\Entity\Badge;
use App\Manager\BadgeManager;
use Doctrine\DBAL\Connection;

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
     * @param array<int,array{externalId:string,name:string,description:string,expiresAt:?\DateTimeImmutable}> $items
     */
    public function bulkUpsert(array $items) : void
    {
        if (!$items) {
            return;
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
}

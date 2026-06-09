<?php

namespace App\Sync\Writer;

use Doctrine\DBAL\Connection;

/**
 * Buffered, ORM-free writer for the volunteer_sync_snapshot table.
 *
 * Per-row save() through the repository forced an extra SELECT + flush
 * for every imported volunteer, which on the full-size prod export
 * doubled MySQL round trips and let the ORM identity map grow. Snapshot
 * writes don't need entity semantics — payload is opaque JSON — so we
 * stream them out as a single INSERT ... ON DUPLICATE KEY UPDATE per
 * flush() call.
 */
class VolunteerSyncSnapshotWriter
{
    /** @var array<int, array{externalId: string, syncedAt: string, payload: string}> */
    private array $buffer = [];

    private Connection $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function queue(string $externalId, \DateTimeImmutable $syncedAt, array $payload) : void
    {
        $this->buffer[] = [
            'externalId' => $externalId,
            'syncedAt'   => $syncedAt->format('Y-m-d H:i:s'),
            'payload'    => json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    public function flush() : void
    {
        if (!$this->buffer) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($this->buffer), '(?, ?, ?)'));
        $params       = [];
        foreach ($this->buffer as $row) {
            $params[] = $row['externalId'];
            $params[] = $row['syncedAt'];
            $params[] = $row['payload'];
        }

        $sql = 'INSERT INTO volunteer_sync_snapshot (external_id, synced_at, payload) VALUES '
            .$placeholders
            .' ON DUPLICATE KEY UPDATE synced_at = VALUES(synced_at), payload = VALUES(payload)';

        $this->conn->executeStatement($sql, $params);
        $this->buffer = [];
    }

    public function bufferedCount() : int
    {
        return count($this->buffer);
    }
}

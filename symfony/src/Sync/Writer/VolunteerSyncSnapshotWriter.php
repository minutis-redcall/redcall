<?php

namespace App\Sync\Writer;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DeadlockException;

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
    private const DEADLOCK_RETRY_ATTEMPTS = 3;
    private const DEADLOCK_RETRY_BASE_DELAY_MS = 25;

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

        // Sort by external_id so every chunk acquires the unique-index
        // next-key locks in the same monotonic order. With 30 concurrent
        // sync-chunk tasks doing INSERT ... ON DUPLICATE KEY UPDATE, an
        // unsorted buffer makes InnoDB grab interleaved gap locks across
        // batches — the classic deadlock cycle we were seeing in prod.
        usort($this->buffer, static fn (array $a, array $b) => strcmp($a['externalId'], $b['externalId']));

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

        // Sorting collapses the vast majority of cycles; the retry catches
        // the residual deadlocks that still slip through (gap locks at
        // chunk boundaries, etc.). Doctrine maps SQLSTATE 40001 → 1213
        // onto DeadlockException so the catch is precise.
        $this->executeWithDeadlockRetry($sql, $params);
        $this->buffer = [];
    }

    public function bufferedCount() : int
    {
        return count($this->buffer);
    }

    /**
     * @param array<int,mixed> $params
     */
    private function executeWithDeadlockRetry(string $sql, array $params) : void
    {
        $attempt = 0;
        while (true) {
            try {
                $this->conn->executeStatement($sql, $params);

                return;
            } catch (DeadlockException $e) {
                $attempt++;
                if ($attempt >= self::DEADLOCK_RETRY_ATTEMPTS) {
                    throw $e;
                }
                // Backoff with a small random jitter so two contending
                // chunks don't immediately collide again on retry.
                usleep((self::DEADLOCK_RETRY_BASE_DELAY_MS * 1000 * $attempt) + random_int(0, 10_000));
            }
        }
    }
}

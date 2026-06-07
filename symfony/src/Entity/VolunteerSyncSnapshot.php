<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Last-known DSI CSV payload for a volunteer.
 *
 * Used as a debug aid: when a volunteer looks off after a sync, an admin can
 * open /management/volunteers/sync-log/{id} to see exactly what was in the
 * latest export — useful when the DSI source itself is missing data.
 *
 * One row per known volunteer (by NIVOL stripped of leading zeros). Upserted
 * by VolunteerImporter at the end of every successful import.
 */
#[ORM\Table]
#[ORM\Index(name: 'volunteer_sync_snapshot_synced_at_idx', columns: ['synced_at'])]
#[ORM\UniqueConstraint(name: 'volunteer_sync_snapshot_external_id_idx', columns: ['external_id'])]
#[ORM\Entity(repositoryClass: \App\Repository\VolunteerSyncSnapshotRepository::class)]
class VolunteerSyncSnapshot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $externalId;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $syncedAt;

    /**
     * JSON-encoded VolunteerRow::toArray() payload (pretty-printed).
     */
    #[ORM\Column(type: 'text')]
    private string $payload;

    public function getId() : ?int
    {
        return $this->id;
    }

    public function getExternalId() : string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId) : void
    {
        $this->externalId = $externalId;
    }

    public function getSyncedAt() : \DateTimeInterface
    {
        return $this->syncedAt;
    }

    public function setSyncedAt(\DateTimeInterface $syncedAt) : void
    {
        $this->syncedAt = $syncedAt;
    }

    public function getPayload() : string
    {
        return $this->payload;
    }

    /**
     * @return array<string,mixed>
     */
    public function getPayloadArray() : array
    {
        return json_decode($this->payload, true) ?? [];
    }

    /**
     * @param array<string,mixed> $data
     */
    public function setPayloadArray(array $data) : void
    {
        $this->payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

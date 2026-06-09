<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Create the volunteer_sync_snapshot table: the new "last-known DSI CSV
 * payload" cache per volunteer, used as a debug aid in the admin UI
 * (see App\Entity\VolunteerSyncSnapshot).
 */
final class Version20260607172114 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create volunteer_sync_snapshot table for per-volunteer last-sync debug view';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('CREATE TABLE volunteer_sync_snapshot ('
            .'id INT AUTO_INCREMENT NOT NULL, '
            .'external_id VARCHAR(64) NOT NULL, '
            .'synced_at DATETIME NOT NULL, '
            .'payload LONGTEXT NOT NULL, '
            .'INDEX volunteer_sync_snapshot_synced_at_idx (synced_at), '
            .'UNIQUE INDEX volunteer_sync_snapshot_external_id_idx (external_id), '
            .'PRIMARY KEY(id)'
            .') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP TABLE volunteer_sync_snapshot');
    }
}

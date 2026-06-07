<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop the legacy `pegass` JSON cache table and rename the
 * `last_pegass_update` columns on `volunteer` and `structure` to
 * `last_synced_at`. The old name was a relic of the dead Pegass HTTP API
 * the cache pretended to mirror.
 *
 * Replaces the legacy import pipeline (PegassFilesCommand →
 * PegassCreateChunks → PegassUpdateChunk → RefreshManager) with the new
 * CSV-driven sync under App\Sync\.
 */
final class Version20260607165230 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Drop legacy pegass table and rename last_pegass_update → last_synced_at on volunteer & structure';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        // 1) Volunteer: rename last_pegass_update and refresh the index name
        $this->addSql('ALTER TABLE volunteer CHANGE last_pegass_update last_synced_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE volunteer DROP INDEX lastpegassupdatex, ADD INDEX last_synced_at_idx (last_synced_at)');

        // 2) Structure: rename last_pegass_update
        $this->addSql('ALTER TABLE structure CHANGE last_pegass_update last_synced_at DATETIME DEFAULT NULL');

        // 3) Drop the legacy pegass JSON cache table
        $this->addSql('DROP TABLE pegass');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        // Recreate the pegass cache table to its last-known shape
        $this->addSql('CREATE TABLE pegass ('
            .'id INT AUTO_INCREMENT NOT NULL, '
            .'identifier VARCHAR(64) DEFAULT NULL, '
            .'external_id VARCHAR(64) DEFAULT NULL, '
            .'parent_identifier VARCHAR(64) DEFAULT NULL, '
            .'type VARCHAR(24) NOT NULL, '
            .'content LONGTEXT DEFAULT NULL, '
            .'updated_at DATETIME NOT NULL, '
            .'enabled TINYINT(1) NOT NULL DEFAULT 1, '
            .'created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', '
            .'INDEX type_update_idx (type, updated_at), '
            .'INDEX typ_ide_par_idx (type, identifier, parent_identifier), '
            .'INDEX enabled_idx (enabled), '
            .'INDEX external_idx (external_id), '
            .'UNIQUE INDEX type_identifier_idx (type, identifier), '
            .'PRIMARY KEY(id)'
            .') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Restore column names
        $this->addSql('ALTER TABLE structure CHANGE last_synced_at last_pegass_update DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE volunteer DROP INDEX last_synced_at_idx, ADD INDEX lastpegassupdatex (last_pegass_update)');
        $this->addSql('ALTER TABLE volunteer CHANGE last_synced_at last_pegass_update DATETIME DEFAULT NULL');
    }
}

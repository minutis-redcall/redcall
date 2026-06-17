<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Split the "triggerer" (User) from the "triggered" (Volunteer).
 *
 * The 1:1 User <-> Volunteer link is removed. User gets its own identity
 * (external_id / NIVOL, first_name, last_name) so its NIVOL can no longer be
 * silently reassigned by volunteer sync. Campaign/Communication authorship
 * moves from a Volunteer FK to a User FK (ON DELETE SET NULL so historical
 * rows survive author deletion).
 *
 * Single-release migration (runs during a short maintenance window):
 *   add columns -> backfill from the still-present FKs -> add unique index
 *   -> drop the old volunteer_id columns/FKs.
 *
 * Annuaire-National users (synthetic "user-annu-*" volunteers) deliberately
 * keep a NULL external_id: they are identified by email/username.
 */
final class Version20260616120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Give User its own identity (external_id/first/last name); move campaign & communication authorship to User; drop the User<->Volunteer link';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        // 1) New identity columns on user + nullable author FKs on campaign/communication.
        //    user.id is a UUID (VARCHAR 36), so the author columns are VARCHAR(36).
        $this->addSql('ALTER TABLE `user` '
            .'ADD external_id VARCHAR(64) DEFAULT NULL, '
            .'ADD first_name VARCHAR(80) DEFAULT NULL, '
            .'ADD last_name VARCHAR(80) DEFAULT NULL');

        $this->addSql('ALTER TABLE campaign ADD user_id VARCHAR(36) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication ADD user_id VARCHAR(36) DEFAULT NULL');

        $this->addSql('ALTER TABLE campaign '
            .'ADD CONSTRAINT FK_campaign_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE communication '
            .'ADD CONSTRAINT FK_communication_user FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        // Canonical Doctrine index names (table+column hash) so the mapping
        // stays in sync — readable aliases would surface as a rename on diff.
        $this->addSql('CREATE INDEX IDX_1F1512DDA76ED395 ON campaign (user_id)');
        $this->addSql('CREATE INDEX IDX_F9AFB5EBA76ED395 ON communication (user_id)');

        // 2) Backfill from the still-present volunteer_id links.
        //    user-annu-* are synthetic Annuaire ids: those users keep NULL external_id.
        $this->addSql('UPDATE `user` u '
            .'INNER JOIN volunteer v ON u.volunteer_id = v.id '
            ."SET u.external_id = v.external_id, u.first_name = v.first_name, u.last_name = v.last_name "
            ."WHERE v.external_id NOT LIKE 'user-annu-%'");

        $this->addSql('UPDATE campaign c '
            .'INNER JOIN `user` u ON u.volunteer_id = c.volunteer_id '
            .'SET c.user_id = u.id WHERE c.volunteer_id IS NOT NULL');
        $this->addSql('UPDATE communication co '
            .'INNER JOIN `user` u ON u.volunteer_id = co.volunteer_id '
            .'SET co.user_id = u.id WHERE co.volunteer_id IS NOT NULL');

        // 3) Enforce one operator per NIVOL (multiple NULLs are allowed by MySQL).
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6499F75D7B0 ON `user` (external_id)');

        // 4) Drop the old links. FK names are auto-generated, so resolve them.
        $this->dropForeignKeyOn('campaign', 'volunteer_id');
        $this->dropForeignKeyOn('communication', 'volunteer_id');
        $this->dropForeignKeyOn('user', 'volunteer_id');

        $this->addSql('ALTER TABLE campaign DROP COLUMN volunteer_id');
        $this->addSql('ALTER TABLE communication DROP COLUMN volunteer_id');
        $this->addSql('ALTER TABLE `user` DROP COLUMN volunteer_id');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        // Restore the old columns + links. Data restoration is best-effort:
        // it can only re-link a user to a volunteer that still shares its NIVOL.
        $this->addSql('ALTER TABLE `user` ADD volunteer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaign ADD volunteer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE communication ADD volunteer_id INT DEFAULT NULL');

        $this->addSql('UPDATE `user` u '
            .'INNER JOIN volunteer v ON v.external_id = u.external_id '
            .'SET u.volunteer_id = v.id WHERE u.external_id IS NOT NULL');
        $this->addSql('UPDATE campaign c '
            .'INNER JOIN `user` u ON c.user_id = u.id '
            .'SET c.volunteer_id = u.volunteer_id');
        $this->addSql('UPDATE communication co '
            .'INNER JOIN `user` u ON co.user_id = u.id '
            .'SET co.volunteer_id = u.volunteer_id');

        $this->addSql('ALTER TABLE `user` '
            .'ADD CONSTRAINT FK_user_volunteer FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_user_volunteer ON `user` (volunteer_id)');
        $this->addSql('ALTER TABLE campaign '
            .'ADD CONSTRAINT FK_campaign_volunteer FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('CREATE INDEX IDX_campaign_volunteer ON campaign (volunteer_id)');
        $this->addSql('ALTER TABLE communication '
            .'ADD CONSTRAINT FK_communication_volunteer FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('CREATE INDEX IDX_communication_volunteer ON communication (volunteer_id)');

        // Drop the new identity/author columns.
        $this->dropForeignKeyOn('campaign', 'user_id');
        $this->dropForeignKeyOn('communication', 'user_id');
        $this->addSql('DROP INDEX UNIQ_8D93D6499F75D7B0 ON `user`');
        $this->addSql('ALTER TABLE campaign DROP COLUMN user_id');
        $this->addSql('ALTER TABLE communication DROP COLUMN user_id');
        $this->addSql('ALTER TABLE `user` DROP COLUMN external_id, DROP COLUMN first_name, DROP COLUMN last_name');
    }

    /**
     * Drops the (auto-named) foreign key constraint that backs a given column,
     * looked up from information_schema so we don't hardcode generated names.
     */
    private function dropForeignKeyOn(string $table, string $column) : void
    {
        $name = $this->connection->fetchOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE '
            .'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c '
            .'AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            ['t' => $table, 'c' => $column]
        );

        if ($name) {
            $this->addSql(sprintf('ALTER TABLE `%s` DROP FOREIGN KEY `%s`', $table, $name));
        }
    }
}

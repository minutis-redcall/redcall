<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Audit trail for volunteer anonymizations — see App\Entity\VolunteerAuditLog.
 * Kept separate from user_audit_log on purpose: rows here are PII-free
 * (no first/last name, no email, no phone) so we can browse and reason about
 * "who anonymized which volunteer and why" without re-creating a privacy hole
 * out of the very deletions the table records.
 */
final class Version20260614135315 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create volunteer_audit_log table for anonymize history';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('CREATE TABLE volunteer_audit_log ('
            .'id INT AUTO_INCREMENT NOT NULL, '
            .'actor_id VARCHAR(36) DEFAULT NULL, '
            .'target_volunteer_id INT DEFAULT NULL, '
            .'created_at DATETIME NOT NULL, '
            .'action VARCHAR(16) NOT NULL, '
            .'actor_label VARCHAR(64) NOT NULL, '
            .'target_external_id VARCHAR(64) DEFAULT NULL, '
            .'target_bound_user_id VARCHAR(36) DEFAULT NULL, '
            .'snapshot LONGTEXT NOT NULL, '
            .'INDEX volunteer_audit_log_created_at_idx (created_at), '
            .'INDEX volunteer_audit_log_target_external_id_idx (target_external_id), '
            .'INDEX volunteer_audit_log_target_bound_user_id_idx (target_bound_user_id), '
            .'INDEX IDX_A93E63B610DAF24A (actor_id), '
            .'INDEX IDX_A93E63B6FB129AAB (target_volunteer_id), '
            .'PRIMARY KEY(id)'
            .') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE volunteer_audit_log '
            .'ADD CONSTRAINT FK_A93E63B610DAF24A FOREIGN KEY (actor_id) REFERENCES user (id) ON DELETE SET NULL, '
            .'ADD CONSTRAINT FK_A93E63B6FB129AAB FOREIGN KEY (target_volunteer_id) REFERENCES volunteer (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP TABLE volunteer_audit_log');
    }
}

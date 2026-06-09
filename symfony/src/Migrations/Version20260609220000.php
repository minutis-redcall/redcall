<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Audit trail for sensitive user changes (create/update/delete) — see
 * App\Entity\UserAuditLog. Both actor and target users are kept by FK
 * with ON DELETE SET NULL so the row survives the deletion of either,
 * and key identifying fields are denormalised so search still works
 * after a hard delete.
 */
final class Version20260609220000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Create user_audit_log table for sensitive user-action history';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('CREATE TABLE user_audit_log ('
            .'id INT AUTO_INCREMENT NOT NULL, '
            .'actor_id VARCHAR(36) DEFAULT NULL, '
            .'target_user_id VARCHAR(36) DEFAULT NULL, '
            .'created_at DATETIME NOT NULL, '
            .'action VARCHAR(10) NOT NULL, '
            .'actor_label VARCHAR(64) NOT NULL, '
            .'target_username VARCHAR(80) DEFAULT NULL, '
            .'target_external_id VARCHAR(64) DEFAULT NULL, '
            .'target_display_name VARCHAR(255) DEFAULT NULL, '
            .'snapshot LONGTEXT NOT NULL, '
            .'INDEX user_audit_log_created_at_idx (created_at), '
            .'INDEX user_audit_log_target_username_idx (target_username), '
            .'INDEX user_audit_log_target_external_id_idx (target_external_id), '
            .'INDEX user_audit_log_target_display_name_idx (target_display_name), '
            .'INDEX IDX_F6014D1110DAF24A (actor_id), '
            .'INDEX IDX_F6014D116C066AFE (target_user_id), '
            .'PRIMARY KEY(id)'
            .') DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE user_audit_log '
            .'ADD CONSTRAINT FK_F6014D1110DAF24A FOREIGN KEY (actor_id) REFERENCES user (id) ON DELETE SET NULL, '
            .'ADD CONSTRAINT FK_F6014D116C066AFE FOREIGN KEY (target_user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform,
            'Migration can only be executed safely on \'mysql\'.'
        );

        $this->addSql('DROP TABLE user_audit_log');
    }
}

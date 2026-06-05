<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231019143514 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        // The unique index on phone.e164 may have either Doctrine's auto-generated
        // name (UNIQ_444F97DDA0BD324A, when schema:update renamed it) or MySQL's
        // default name (e164, from the original CREATE TABLE inline UNIQUE).
        // Drop whichever exists.
        $indexes = $this->connection->fetchAllAssociative(
            "SELECT DISTINCT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'phone'
               AND COLUMN_NAME = 'e164'
               AND NON_UNIQUE = 0"
        );
        foreach ($indexes as $row) {
            $this->addSql(sprintf('DROP INDEX `%s` ON phone', $row['INDEX_NAME']));
        }
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(!$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\AbstractMySQLPlatform, 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_444F97DDA0BD324A ON phone (e164)');
    }
}

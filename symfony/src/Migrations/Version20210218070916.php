<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Ramsey\Uuid\Uuid;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210218070916 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $badgeIds = array_column($this->connection->fetchAllAssociative('SELECT id FROM badge WHERE external_id IS NULL'), 'id');
        foreach ($badgeIds as $badgeId) {
            $this->addSql('UPDATE badge SET external_id = :uuid WHERE id = :id', [
                'uuid' => Uuid::uuid4(),
                'id'   => $badgeId,
            ]);
        }
        $this->addSql('ALTER TABLE badge CHANGE external_id external_id VARCHAR(64) NOT NULL');

        $this->addSql('ALTER TABLE category ADD external_id VARCHAR(64) NULL DEFAULT NULL');
        $categoryIds = array_column($this->connection->fetchAllAssociative('SELECT id FROM category'), 'id');
        foreach ($categoryIds as $categoryId) {
            $this->addSql('UPDATE category SET external_id = :uuid WHERE id = :id', [
                'uuid' => Uuid::uuid4(),
                'id'   => $categoryId,
            ]);
        }
        $this->addSql('ALTER TABLE category CHANGE external_id external_id VARCHAR(64) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C19F75D7B0 ON category (external_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE badge CHANGE CHANGE external_id external_id VARCHAR(64) DEFAULT NULL');
        $this->addSql('DROP INDEX UNIQ_64C19C19F75D7B0 ON category');
        $this->addSql('ALTER TABLE category DROP external_id');
    }
}

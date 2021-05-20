<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210511225138 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE fake_operation (id INT AUTO_INCREMENT NOT NULL, structure_external_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, owner_email VARCHAR(255) DEFAULT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE fake_operation_resource (id INT AUTO_INCREMENT NOT NULL, operation_id INT DEFAULT NULL, volunteer_external_id VARCHAR(64) DEFAULT NULL, INDEX IDX_D7DE50D644AC3583 (operation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE fake_operation_resource ADD CONSTRAINT FK_D7DE50D644AC3583 FOREIGN KEY (operation_id) REFERENCES fake_operation (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE fake_operation_resource DROP FOREIGN KEY FK_D7DE50D644AC3583');
        $this->addSql('DROP TABLE fake_operation');
        $this->addSql('DROP TABLE fake_operation_resource');
    }
}

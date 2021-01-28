<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210128144311 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE report (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(32) NOT NULL, message_count INT NOT NULL, answer_count INT NOT NULL, choice_count INT NOT NULL, bounce_count INT NOT NULL, answer_ratio INT NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE report_repartition (id INT AUTO_INCREMENT NOT NULL, structure_id INT DEFAULT NULL, report_id INT NOT NULL, ratio INT NOT NULL, total_messages INT NOT NULL, share_messages INT NOT NULL, INDEX IDX_33AF3AA82534008B (structure_id), INDEX IDX_33AF3AA84BD2A4C0 (report_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE report_repartition ADD CONSTRAINT FK_33AF3AA82534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE report_repartition ADD CONSTRAINT FK_33AF3AA84BD2A4C0 FOREIGN KEY (report_id) REFERENCES report (id)');
        $this->addSql('ALTER TABLE communication ADD report_id INT DEFAULT NULL, ADD last_activity_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE communication ADD CONSTRAINT FK_F9AFB5EB4BD2A4C0 FOREIGN KEY (report_id) REFERENCES report (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F9AFB5EB4BD2A4C0 ON communication (report_id)');
        $this->addSql('CREATE INDEX last_activity_idx ON communication (last_activity_at)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE communication DROP FOREIGN KEY FK_F9AFB5EB4BD2A4C0');
        $this->addSql('ALTER TABLE report_repartition DROP FOREIGN KEY FK_33AF3AA84BD2A4C0');
        $this->addSql('DROP TABLE report');
        $this->addSql('DROP TABLE report_repartition');
        $this->addSql('DROP INDEX UNIQ_F9AFB5EB4BD2A4C0 ON communication');
        $this->addSql('DROP INDEX last_activity_idx ON communication');
        $this->addSql('ALTER TABLE communication DROP report_id, DROP last_activity_at');
    }
}

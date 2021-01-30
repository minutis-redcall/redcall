<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210130101155 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM report_repartition');
        $this->addSql('UPDATE communication SET report_id = NULL, last_activity_at = "2020-01-01"');
        $this->addSql('DELETE FROM report');

        $this->addSql('ALTER TABLE report ADD error_count INT NOT NULL, DROP answer_ratio');
        $this->addSql('ALTER TABLE report_repartition ADD error_count INT NOT NULL, DROP answer_ratio');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE report ADD answer_ratio DOUBLE PRECISION DEFAULT NULL, DROP error_count');
        $this->addSql('ALTER TABLE report_repartition ADD answer_ratio DOUBLE PRECISION DEFAULT NULL, DROP error_count');
    }
}

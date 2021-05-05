<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210501071615 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function isTransactional() : bool
    {
        return false;
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX nivolx ON volunteer');
        $this->addSql('DROP INDEX UNIQ_5140DEDB5013C841 ON volunteer');
        $this->addSql('ALTER TABLE volunteer DROP nivol');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer ADD nivol VARCHAR(80) NOT NULL');
        $this->addSql('CREATE INDEX nivolx ON volunteer (nivol)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5140DEDB5013C841 ON volunteer (nivol)');
    }
}

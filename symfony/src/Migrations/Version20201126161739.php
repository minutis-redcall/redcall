<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201126161739 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX nationalx ON phone (national)');
        $this->addSql('CREATE INDEX internationalx ON phone (international)');
        $this->addSql('ALTER TABLE phone RENAME INDEX e164 TO UNIQ_444F97DDA0BD324A');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX nationalx ON phone');
        $this->addSql('DROP INDEX internationalx ON phone');
        $this->addSql('ALTER TABLE phone RENAME INDEX uniq_444f97dda0bd324a TO e164');
    }
}

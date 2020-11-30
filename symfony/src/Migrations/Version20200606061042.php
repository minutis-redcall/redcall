<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200606061042 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX enabledx ON volunteer');
        $this->addSql('ALTER TABLE volunteer ADD phone_number_optin TINYINT(1) NOT NULL DEFAULT 1, ADD email_optin TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql('CREATE INDEX phone_number_optinx ON volunteer (phone_number_optin)');
        $this->addSql('CREATE INDEX email_optinx ON volunteer (email_optin)');
        $this->addSql('CREATE INDEX enabledx ON volunteer (enabled)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX phone_number_optinx ON volunteer');
        $this->addSql('DROP INDEX email_optinx ON volunteer');
        $this->addSql('DROP INDEX enabledx ON volunteer');
        $this->addSql('ALTER TABLE volunteer DROP phone_number_optin, DROP email_optin');
        $this->addSql('CREATE INDEX enabledx ON volunteer (id, enabled)');
    }
}

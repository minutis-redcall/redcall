<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181104181856 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer ADD email VARCHAR(80) DEFAULT NULL, ADD enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD locked TINYINT(1) DEFAULT \'0\' NOT NULL, ADD errors TEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\'');
        $this->addSql('ALTER TABLE captcha CHANGE timestamp timestamp INT UNSIGNED NOT NULL, CHANGE grace grace INT NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE captcha CHANGE timestamp timestamp INT UNSIGNED DEFAULT NULL, CHANGE grace grace INT DEFAULT NULL');
        $this->addSql('ALTER TABLE volunteer DROP email, DROP enabled, DROP locked, DROP errors');
    }
}

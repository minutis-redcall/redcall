<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200329161557 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX name_idx ON structure');
        $this->addSql('CREATE INDEX name_idx ON structure (name)');
        $this->addSql('ALTER TABLE communication ADD volunteer_id INT DEFAULT NULL, CHANGE label label VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE communication ADD CONSTRAINT FK_F9AFB5EB8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('CREATE INDEX IDX_F9AFB5EB8EFAB6B1 ON communication (volunteer_id)');
        $this->addSql('DROP INDEX price_idx ON twilio_message');
        $this->addSql('CREATE INDEX price_idx ON twilio_message (price)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE communication DROP FOREIGN KEY FK_F9AFB5EB8EFAB6B1');
        $this->addSql('DROP INDEX IDX_F9AFB5EB8EFAB6B1 ON communication');
        $this->addSql('ALTER TABLE communication DROP volunteer_id, CHANGE label label VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP INDEX name_idx ON structure');
        $this->addSql('CREATE INDEX name_idx ON structure (name(191))');
        $this->addSql('DROP INDEX price_idx ON twilio_message');
        $this->addSql('CREATE INDEX price_idx ON twilio_message (price(191))');
    }
}

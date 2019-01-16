<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180601015010 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE communication_message');
        $this->addSql('ALTER TABLE campaign ADD active INT DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE communication_message (communication_id INT NOT NULL, message_id INT NOT NULL, INDEX IDX_C36C19251C2D1E0C (communication_id), INDEX IDX_C36C1925537A1329 (message_id), PRIMARY KEY(communication_id, message_id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE communication_message ADD CONSTRAINT FK_C36C19251C2D1E0C FOREIGN KEY (communication_id) REFERENCES communication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE communication_message ADD CONSTRAINT FK_C36C1925537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign DROP active');
    }
}

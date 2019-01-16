<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180601043546 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F1C2D1E0C');
        $this->addSql('DROP INDEX IDX_B6BD307F1C2D1E0C ON message');
        $this->addSql('ALTER TABLE message CHANGE communication_id choice_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F998666D1 FOREIGN KEY (choice_id) REFERENCES choice (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F998666D1 ON message (choice_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F998666D1');
        $this->addSql('DROP INDEX IDX_B6BD307F998666D1 ON message');
        $this->addSql('ALTER TABLE message CHANGE choice_id communication_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F1C2D1E0C FOREIGN KEY (communication_id) REFERENCES communication (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F1C2D1E0C ON message (communication_id)');
    }
}

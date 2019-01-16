<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181021095938 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            INSERT INTO answer (message_id, choice_id, raw, received_at)
            SELECT id, choice_id, answer, now()
            FROM message
            WHERE answer IS NOT NULL 
            AND answer != ""
        ');

        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F998666D1');
        $this->addSql('DROP INDEX IDX_B6BD307F998666D1 ON message');
        $this->addSql('ALTER TABLE message DROP choice_id, DROP answer');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE message ADD choice_id INT DEFAULT NULL, ADD answer LONGTEXT DEFAULT NULL COLLATE utf8mb4_unicode_ci');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F998666D1 FOREIGN KEY (choice_id) REFERENCES choice (id)');
        $this->addSql('CREATE INDEX IDX_B6BD307F998666D1 ON message (choice_id)');
    }
}

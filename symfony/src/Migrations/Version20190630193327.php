<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190630193327 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE answer_choice (answer_id INT NOT NULL, choice_id INT NOT NULL, INDEX IDX_33526035AA334807 (answer_id), INDEX IDX_33526035998666D1 (choice_id), PRIMARY KEY(answer_id, choice_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE answer_choice ADD CONSTRAINT FK_33526035AA334807 FOREIGN KEY (answer_id) REFERENCES answer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE answer_choice ADD CONSTRAINT FK_33526035998666D1 FOREIGN KEY (choice_id) REFERENCES choice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A25998666D1');

        $choices = $this->connection->fetchAll('
            SELECT id as answer_id, choice_id
            FROM answer
            WHERE choice_id IS NOT NULL
        ');

        foreach ($choices as $choice) {
            $this->addSql('INSERT IGNORE INTO answer_choice (answer_id, choice_id) VALUES (:answer_id, :choice_id)', $choice);
        }

        $this->addSql('DROP INDEX IDX_DADD4A25998666D1 ON answer');
        $this->addSql('ALTER TABLE answer ADD unclear TINYINT(1) NOT NULL, DROP choice_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE answer ADD choice_id INT DEFAULT NULL, DROP unclear');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A25998666D1 FOREIGN KEY (choice_id) REFERENCES choice (id)');
        $this->addSql('CREATE INDEX IDX_DADD4A25998666D1 ON answer (choice_id)');

        $choices = $this->connection->fetchAll('SELECT answer_id, choice_id FROM answer_choice');

        foreach ($choices as $choice) {
            $this->addSql('
              UPDATE answer
              SET choice_id = :choice_id
              WHERE id = :answer_id
              AND choice_id IS NULL
              LIMIT 1
            ', $choice);
        }

        $this->addSql('DROP TABLE answer_choice');
    }
}

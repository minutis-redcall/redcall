<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190602172404 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            INSERT IGNORE INTO volunteer_tag
            SELECT 4 as tag_id, volunteer_id
            FROM volunteer_tag
            WHERE tag_id = 5
        ');

        $this->addSql('
            INSERT IGNORE INTO volunteer_tag
            SELECT 6 as tag_id, volunteer_id
            FROM volunteer_tag
            WHERE tag_id = 7
        ');

        $this->addSql('
            INSERT IGNORE INTO volunteer_tag
            SELECT 10 as tag_id, volunteer_id
            FROM volunteer_tag
            WHERE tag_id = 11
        ');

        $this->addSql('DELETE FROM tag WHERE id IN (5, 7, 11)');

        $this->addSql('UPDATE tag SET label = "pse_1" WHERE id = 4');
        $this->addSql('UPDATE tag SET label = "pse_2" WHERE id = 6');
        $this->addSql('UPDATE tag SET label = "ci" WHERE id = 10');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('UPDATE tag SET label = "pse_1_i" WHERE id = 4');
        $this->addSql('UPDATE tag SET label = "pse_2_i" WHERE id = 6');
        $this->addSql('UPDATE tag SET label = "ci_i" WHERE id = 10');

        $this->addSql('INSERT INTO tag (id, label) VALUES (5, "pse_1_r")');
        $this->addSql('INSERT INTO tag (id, label) VALUES (7, "pse_2_r")');
        $this->addSql('INSERT INTO tag (id, label) VALUES (11, "ci_r")');
    }
}

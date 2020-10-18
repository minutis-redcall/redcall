<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200119111411 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_information_structure (user_information_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_11897F164575EE58 (user_information_id), INDEX IDX_11897F162534008B (structure_id), PRIMARY KEY(user_information_id, structure_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_information_structure ADD CONSTRAINT FK_11897F164575EE58 FOREIGN KEY (user_information_id) REFERENCES user_information (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_information_structure ADD CONSTRAINT FK_11897F162534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE user_information_structure');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220109064157 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE volunteer_list (id INT AUTO_INCREMENT NOT NULL, structure_id INT NOT NULL, name VARCHAR(64) NOT NULL, audience LONGTEXT NOT NULL, INDEX IDX_F279E9342534008B (structure_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE volunteer_list_volunteer (volunteer_list_id INT NOT NULL, volunteer_id INT NOT NULL, INDEX IDX_ABAFCB7FE34609B3 (volunteer_list_id), INDEX IDX_ABAFCB7F8EFAB6B1 (volunteer_id), PRIMARY KEY(volunteer_list_id, volunteer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE volunteer_list ADD CONSTRAINT FK_F279E9342534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE volunteer_list_volunteer ADD CONSTRAINT FK_ABAFCB7FE34609B3 FOREIGN KEY (volunteer_list_id) REFERENCES volunteer_list (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_list_volunteer ADD CONSTRAINT FK_ABAFCB7F8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer_list_volunteer DROP FOREIGN KEY FK_ABAFCB7FE34609B3');
        $this->addSql('DROP TABLE volunteer_list');
        $this->addSql('DROP TABLE volunteer_list_volunteer');
    }
}

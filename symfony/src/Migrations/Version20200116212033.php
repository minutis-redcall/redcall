<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200116212033 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE volunteer_structure (volunteer_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_F596580C8EFAB6B1 (volunteer_id), INDEX IDX_F596580C2534008B (structure_id), PRIMARY KEY(volunteer_id, structure_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE volunteer_structure ADD CONSTRAINT FK_F596580C8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_structure ADD CONSTRAINT FK_F596580C2534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer DROP FOREIGN KEY FK_5140DEDB2534008B');
        $this->addSql('DROP INDEX IDX_5140DEDB2534008B ON volunteer');
        $this->addSql('ALTER TABLE volunteer DROP structure_id');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE volunteer_structure');
        $this->addSql('ALTER TABLE volunteer ADD structure_id INT NOT NULL');
        $this->addSql('ALTER TABLE volunteer ADD CONSTRAINT FK_5140DEDB2534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5140DEDB2534008B ON volunteer (structure_id)');
    }
}

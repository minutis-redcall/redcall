<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201231151339 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE badge_restriction');
        $this->addSql('DROP TABLE badge_visibility');
        $this->addSql('ALTER TABLE badge DROP restricted');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE badge_restriction (badge_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_9460A9192534008B (structure_id), INDEX IDX_9460A919F7A2C2FC (badge_id), PRIMARY KEY(badge_id, structure_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE badge_visibility (badge_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_A6DDB8022534008B (structure_id), INDEX IDX_A6DDB802F7A2C2FC (badge_id), PRIMARY KEY(badge_id, structure_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE badge_restriction ADD CONSTRAINT FK_9460A9192534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE badge_restriction ADD CONSTRAINT FK_9460A919F7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE badge_visibility ADD CONSTRAINT FK_A6DDB8022534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE badge_visibility ADD CONSTRAINT FK_A6DDB802F7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE badge ADD restricted TINYINT(1) NOT NULL');
    }
}

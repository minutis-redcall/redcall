<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201219084752 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE volunteer_badge (volunteer_id INT NOT NULL, badge_id INT NOT NULL, INDEX IDX_CC9E95EF8EFAB6B1 (volunteer_id), INDEX IDX_CC9E95EFF7A2C2FC (badge_id), PRIMARY KEY(volunteer_id, badge_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE volunteer_badge ADD CONSTRAINT FK_CC9E95EF8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_badge ADD CONSTRAINT FK_CC9E95EFF7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE');
        $this->addSql('DROP TABLE badge_volunteer');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE badge_volunteer (badge_id INT NOT NULL, volunteer_id INT NOT NULL, INDEX IDX_60EC814F8EFAB6B1 (volunteer_id), INDEX IDX_60EC814FF7A2C2FC (badge_id), PRIMARY KEY(badge_id, volunteer_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE badge_volunteer ADD CONSTRAINT FK_60EC814F8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE badge_volunteer ADD CONSTRAINT FK_60EC814FF7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('DROP TABLE volunteer_badge');
    }
}

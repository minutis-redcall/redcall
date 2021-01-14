<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210114073207 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer_tag DROP FOREIGN KEY FK_D4B5A6BCBAD26311');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE volunteer_tag');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(200) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE volunteer_tag (volunteer_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_D4B5A6BC8EFAB6B1 (volunteer_id), INDEX IDX_D4B5A6BCBAD26311 (tag_id), PRIMARY KEY(volunteer_id, tag_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE volunteer_tag ADD CONSTRAINT FK_D4B5A6BC8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_tag ADD CONSTRAINT FK_D4B5A6BCBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON UPDATE NO ACTION ON DELETE CASCADE');
    }
}

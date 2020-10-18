<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181017173229 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE volunteer_status');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE volunteer_status (id INT AUTO_INCREMENT NOT NULL, campaign_id INT DEFAULT NULL, volunteer_id INT DEFAULT NULL, checked_in_at DATETIME NOT NULL, checked_out_at DATETIME DEFAULT NULL, INDEX IDX_288198F1F639F774 (campaign_id), INDEX IDX_288198F18EFAB6B1 (volunteer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE volunteer_status ADD CONSTRAINT FK_288198F18EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('ALTER TABLE volunteer_status ADD CONSTRAINT FK_288198F1F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id)');
    }
}

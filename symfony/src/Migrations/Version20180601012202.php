<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180601012202 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE campaign (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE choice (id INT AUTO_INCREMENT NOT NULL, communication_id INT DEFAULT NULL, code INT NOT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_C1AB5A921C2D1E0C (communication_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE communication (id INT AUTO_INCREMENT NOT NULL, campaign_id INT DEFAULT NULL, body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_F9AFB5EBF639F774 (campaign_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE communication_message (communication_id INT NOT NULL, message_id INT NOT NULL, INDEX IDX_C36C19251C2D1E0C (communication_id), INDEX IDX_C36C1925537A1329 (message_id), PRIMARY KEY(communication_id, message_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, volunteer_id INT DEFAULT NULL, message_id VARCHAR(20) NOT NULL, sent TINYINT(1) NOT NULL, received TINYINT(1) NOT NULL, answer LONGTEXT DEFAULT NULL, INDEX IDX_B6BD307F8EFAB6B1 (volunteer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tag (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(200) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE volunteer (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, phone_number VARCHAR(20) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE volunteer_tag (volunteer_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_D4B5A6BC8EFAB6B1 (volunteer_id), INDEX IDX_D4B5A6BCBAD26311 (tag_id), PRIMARY KEY(volunteer_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE choice ADD CONSTRAINT FK_C1AB5A921C2D1E0C FOREIGN KEY (communication_id) REFERENCES communication (id)');
        $this->addSql('ALTER TABLE communication ADD CONSTRAINT FK_F9AFB5EBF639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id)');
        $this->addSql('ALTER TABLE communication_message ADD CONSTRAINT FK_C36C19251C2D1E0C FOREIGN KEY (communication_id) REFERENCES communication (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE communication_message ADD CONSTRAINT FK_C36C1925537A1329 FOREIGN KEY (message_id) REFERENCES message (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('ALTER TABLE volunteer_tag ADD CONSTRAINT FK_D4B5A6BC8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_tag ADD CONSTRAINT FK_D4B5A6BCBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE communication DROP FOREIGN KEY FK_F9AFB5EBF639F774');
        $this->addSql('ALTER TABLE choice DROP FOREIGN KEY FK_C1AB5A921C2D1E0C');
        $this->addSql('ALTER TABLE communication_message DROP FOREIGN KEY FK_C36C19251C2D1E0C');
        $this->addSql('ALTER TABLE communication_message DROP FOREIGN KEY FK_C36C1925537A1329');
        $this->addSql('ALTER TABLE volunteer_tag DROP FOREIGN KEY FK_D4B5A6BCBAD26311');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F8EFAB6B1');
        $this->addSql('ALTER TABLE volunteer_tag DROP FOREIGN KEY FK_D4B5A6BC8EFAB6B1');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE choice');
        $this->addSql('DROP TABLE communication');
        $this->addSql('DROP TABLE communication_message');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE volunteer');
        $this->addSql('DROP TABLE volunteer_tag');
    }
}

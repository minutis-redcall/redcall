<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200130125616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE IF NOT EXISTS user_information (id INT AUTO_INCREMENT NOT NULL, user_id VARCHAR(36) NOT NULL, volunteer_id INT DEFAULT NULL, locale VARCHAR(10) DEFAULT NULL, nivol VARCHAR(80) DEFAULT NULL, INDEX IDX_8062D116A76ED395 (user_id), INDEX IDX_8062D1168EFAB6B1 (volunteer_id), INDEX nivol_idx (nivol), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS user_information_structure (user_information_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_11897F164575EE58 (user_information_id), INDEX IDX_11897F162534008B (structure_id), PRIMARY KEY(user_information_id, structure_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS volunteer (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(80) DEFAULT NULL, nivol VARCHAR(80) NOT NULL, first_name VARCHAR(80) DEFAULT NULL, last_name VARCHAR(80) DEFAULT NULL, phone_number VARCHAR(20) DEFAULT NULL, email VARCHAR(80) DEFAULT NULL, enabled TINYINT(1) DEFAULT \'1\' NOT NULL, locked TINYINT(1) DEFAULT \'0\' NOT NULL, minor TINYINT(1) DEFAULT \'0\' NOT NULL, last_pegass_update DATETIME DEFAULT NULL, report TEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', UNIQUE INDEX UNIQ_5140DEDB5013C841 (nivol), INDEX nivolx (nivol), INDEX phone_numberx (phone_number), INDEX emailx (email), INDEX enabledx (id, enabled), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS volunteer_tag (volunteer_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_D4B5A6BC8EFAB6B1 (volunteer_id), INDEX IDX_D4B5A6BCBAD26311 (tag_id), PRIMARY KEY(volunteer_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS volunteer_structure (volunteer_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_F596580C8EFAB6B1 (volunteer_id), INDEX IDX_F596580C2534008B (structure_id), PRIMARY KEY(volunteer_id, structure_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS structure (id INT AUTO_INCREMENT NOT NULL, parent_structure_id INT DEFAULT NULL, identifier INT NOT NULL, type VARCHAR(16) NOT NULL, name VARCHAR(255) NOT NULL, enabled TINYINT(1) NOT NULL, president VARCHAR(255) DEFAULT NULL, last_pegass_update DATETIME DEFAULT NULL, INDEX IDX_6F0137EA755A5DA5 (parent_structure_id), INDEX name_idx (name), UNIQUE INDEX identifier_idx (identifier), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS tag (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(200) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS campaign (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, type VARCHAR(80) NOT NULL, created_at DATETIME NOT NULL, active TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS campaign_structure (campaign_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_AF892B91F639F774 (campaign_id), INDEX IDX_AF892B912534008B (structure_id), PRIMARY KEY(campaign_id, structure_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS geo_location (id INT AUTO_INCREMENT NOT NULL, message_id INT DEFAULT NULL, longitude VARCHAR(32) NOT NULL, latitude VARCHAR(32) NOT NULL, accuracy INT NOT NULL, heading INT DEFAULT NULL, datetime DATETIME NOT NULL, UNIQUE INDEX UNIQ_B027FE6A537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS message (id INT AUTO_INCREMENT NOT NULL, volunteer_id INT DEFAULT NULL, communication_id INT DEFAULT NULL, message_id VARCHAR(20) DEFAULT NULL, sent TINYINT(1) NOT NULL, cost DOUBLE PRECISION NOT NULL, code VARBINARY(8) DEFAULT NULL, prefix VARCHAR(8) DEFAULT NULL, INDEX IDX_B6BD307F8EFAB6B1 (volunteer_id), INDEX IDX_B6BD307F1C2D1E0C (communication_id), INDEX message_idx (message_id), INDEX codex (code), INDEX prefixx (volunteer_id, prefix), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS prefilled_answers (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, colors LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:simple_array)\', answers LONGTEXT NOT NULL COMMENT \'(DC2Type:simple_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS answer (id INT AUTO_INCREMENT NOT NULL, message_id INT DEFAULT NULL, raw LONGTEXT DEFAULT NULL, received_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, unclear TINYINT(1) NOT NULL, by_admin VARCHAR(64) DEFAULT NULL, INDEX IDX_DADD4A25537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS answer_choice (answer_id INT NOT NULL, choice_id INT NOT NULL, INDEX IDX_33526035AA334807 (answer_id), INDEX IDX_33526035998666D1 (choice_id), PRIMARY KEY(answer_id, choice_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS choice (id INT AUTO_INCREMENT NOT NULL, communication_id INT DEFAULT NULL, code VARCHAR(2) NOT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_C1AB5A921C2D1E0C (communication_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS communication (id INT AUTO_INCREMENT NOT NULL, campaign_id INT DEFAULT NULL, label VARCHAR(20) DEFAULT NULL, type VARCHAR(20) NOT NULL, subject VARCHAR(80) DEFAULT NULL, body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, geo_location TINYINT(1) NOT NULL, multiple_answer TINYINT(1) NOT NULL, INDEX IDX_F9AFB5EBF639F774 (campaign_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS user (id VARCHAR(36) NOT NULL, username VARCHAR(64) NOT NULL, password VARCHAR(72) NOT NULL, is_verified TINYINT(1) NOT NULL, is_trusted TINYINT(1) NOT NULL, is_admin TINYINT(1) NOT NULL, registered_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS password_recovery (username VARCHAR(64) NOT NULL, uuid VARCHAR(36) NOT NULL, timestamp INT UNSIGNED NOT NULL, UNIQUE INDEX uuid_idx (uuid), PRIMARY KEY(username)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS captcha (ip INT UNSIGNED NOT NULL, timestamp INT UNSIGNED NOT NULL, grace INT NOT NULL, whitelisted TINYINT(1) NOT NULL, PRIMARY KEY(ip)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS email_verification (username VARCHAR(64) NOT NULL, uuid VARCHAR(36) NOT NULL, type VARCHAR(36) NOT NULL, timestamp INT UNSIGNED NOT NULL, UNIQUE INDEX uuid_idx (uuid), PRIMARY KEY(username)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS pegass (id INT AUTO_INCREMENT NOT NULL, identifier VARCHAR(64) DEFAULT NULL, parent_identifier VARCHAR(64) DEFAULT NULL, type VARCHAR(24) NOT NULL, content LONGTEXT DEFAULT NULL, updated_at DATETIME NOT NULL, enabled TINYINT(1) NOT NULL, INDEX type_update_idx (type, updated_at), INDEX typ_ide_par_idx (type, identifier, parent_identifier), INDEX enabled_idx (enabled), UNIQUE INDEX type_identifier_idx (type, identifier), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS fake_sms (id INT AUTO_INCREMENT NOT NULL, phone_number VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, direction VARCHAR(16) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS fake_email (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, subject VARCHAR(255) DEFAULT NULL, body LONGTEXT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE IF NOT EXISTS setting (id INT AUTO_INCREMENT NOT NULL, property VARCHAR(190) NOT NULL, value LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_9F74B8988BF21CDE (property), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_information ADD CONSTRAINT FK_8062D116A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_information ADD CONSTRAINT FK_8062D1168EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('ALTER TABLE user_information_structure ADD CONSTRAINT FK_11897F164575EE58 FOREIGN KEY (user_information_id) REFERENCES user_information (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_information_structure ADD CONSTRAINT FK_11897F162534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_tag ADD CONSTRAINT FK_D4B5A6BC8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_tag ADD CONSTRAINT FK_D4B5A6BCBAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_structure ADD CONSTRAINT FK_F596580C8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_structure ADD CONSTRAINT FK_F596580C2534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE structure ADD CONSTRAINT FK_6F0137EA755A5DA5 FOREIGN KEY (parent_structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE campaign_structure ADD CONSTRAINT FK_AF892B91F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign_structure ADD CONSTRAINT FK_AF892B912534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE geo_location ADD CONSTRAINT FK_B027FE6A537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F1C2D1E0C FOREIGN KEY (communication_id) REFERENCES communication (id)');
        $this->addSql('ALTER TABLE answer ADD CONSTRAINT FK_DADD4A25537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
        $this->addSql('ALTER TABLE answer_choice ADD CONSTRAINT FK_33526035AA334807 FOREIGN KEY (answer_id) REFERENCES answer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE answer_choice ADD CONSTRAINT FK_33526035998666D1 FOREIGN KEY (choice_id) REFERENCES choice (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE choice ADD CONSTRAINT FK_C1AB5A921C2D1E0C FOREIGN KEY (communication_id) REFERENCES communication (id)');
        $this->addSql('ALTER TABLE communication ADD CONSTRAINT FK_F9AFB5EBF639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_information_structure DROP FOREIGN KEY FK_11897F164575EE58');
        $this->addSql('ALTER TABLE user_information DROP FOREIGN KEY FK_8062D1168EFAB6B1');
        $this->addSql('ALTER TABLE volunteer_tag DROP FOREIGN KEY FK_D4B5A6BC8EFAB6B1');
        $this->addSql('ALTER TABLE volunteer_structure DROP FOREIGN KEY FK_F596580C8EFAB6B1');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F8EFAB6B1');
        $this->addSql('ALTER TABLE user_information_structure DROP FOREIGN KEY FK_11897F162534008B');
        $this->addSql('ALTER TABLE volunteer_structure DROP FOREIGN KEY FK_F596580C2534008B');
        $this->addSql('ALTER TABLE structure DROP FOREIGN KEY FK_6F0137EA755A5DA5');
        $this->addSql('ALTER TABLE campaign_structure DROP FOREIGN KEY FK_AF892B912534008B');
        $this->addSql('ALTER TABLE volunteer_tag DROP FOREIGN KEY FK_D4B5A6BCBAD26311');
        $this->addSql('ALTER TABLE campaign_structure DROP FOREIGN KEY FK_AF892B91F639F774');
        $this->addSql('ALTER TABLE communication DROP FOREIGN KEY FK_F9AFB5EBF639F774');
        $this->addSql('ALTER TABLE geo_location DROP FOREIGN KEY FK_B027FE6A537A1329');
        $this->addSql('ALTER TABLE answer DROP FOREIGN KEY FK_DADD4A25537A1329');
        $this->addSql('ALTER TABLE answer_choice DROP FOREIGN KEY FK_33526035AA334807');
        $this->addSql('ALTER TABLE answer_choice DROP FOREIGN KEY FK_33526035998666D1');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F1C2D1E0C');
        $this->addSql('ALTER TABLE choice DROP FOREIGN KEY FK_C1AB5A921C2D1E0C');
        $this->addSql('ALTER TABLE user_information DROP FOREIGN KEY FK_8062D116A76ED395');
        $this->addSql('DROP TABLE user_information');
        $this->addSql('DROP TABLE user_information_structure');
        $this->addSql('DROP TABLE volunteer');
        $this->addSql('DROP TABLE volunteer_tag');
        $this->addSql('DROP TABLE volunteer_structure');
        $this->addSql('DROP TABLE structure');
        $this->addSql('DROP TABLE tag');
        $this->addSql('DROP TABLE campaign');
        $this->addSql('DROP TABLE campaign_structure');
        $this->addSql('DROP TABLE geo_location');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE prefilled_answers');
        $this->addSql('DROP TABLE answer');
        $this->addSql('DROP TABLE answer_choice');
        $this->addSql('DROP TABLE choice');
        $this->addSql('DROP TABLE communication');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE password_recovery');
        $this->addSql('DROP TABLE captcha');
        $this->addSql('DROP TABLE email_verification');
        $this->addSql('DROP TABLE pegass');
        $this->addSql('DROP TABLE fake_sms');
        $this->addSql('DROP TABLE fake_email');
        $this->addSql('DROP TABLE setting');
    }
}

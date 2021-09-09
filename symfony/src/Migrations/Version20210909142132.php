<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210909142132 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE geo_location');
        $this->addSql('ALTER TABLE communication DROP geo_location');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE geo_location (id INT AUTO_INCREMENT NOT NULL, message_id INT DEFAULT NULL, longitude VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, latitude VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, accuracy INT NOT NULL, datetime DATETIME NOT NULL, heading INT DEFAULT NULL, UNIQUE INDEX UNIQ_B027FE6A537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE geo_location ADD CONSTRAINT FK_B027FE6A537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
        $this->addSql('ALTER TABLE answer CHANGE message_id message_id INT DEFAULT NULL, CHANGE by_admin by_admin VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE badge CHANGE category_id category_id INT DEFAULT NULL, CHANGE parent_id parent_id INT DEFAULT NULL, CHANGE synonym_id synonym_id INT DEFAULT NULL, CHANGE description description VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE triggering_priority triggering_priority INT DEFAULT 500 NOT NULL');
        $this->addSql('ALTER TABLE campaign CHANGE volunteer_id volunteer_id INT DEFAULT NULL, CHANGE operation_id operation_id INT DEFAULT NULL, CHANGE notes_updated_at notes_updated_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE category CHANGE priority priority INT DEFAULT NULL');
        $this->addSql('ALTER TABLE choice CHANGE communication_id communication_id INT DEFAULT NULL, CHANGE operation_id operation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE communication ADD geo_location TINYINT(1) NOT NULL, CHANGE campaign_id campaign_id INT DEFAULT NULL, CHANGE volunteer_id volunteer_id INT DEFAULT NULL, CHANGE report_id report_id INT DEFAULT NULL, CHANGE label label VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE subject subject VARCHAR(80) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE last_activity_at last_activity_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE cost CHANGE message_id message_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE fake_email CHANGE subject subject VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE fake_operation CHANGE name name VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE owner_email owner_email VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE fake_operation_resource CHANGE operation_id operation_id INT DEFAULT NULL, CHANGE volunteer_external_id volunteer_external_id VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE media CHANGE communication_id communication_id INT DEFAULT NULL, CHANGE expires_at expires_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE message CHANGE volunteer_id volunteer_id INT DEFAULT NULL, CHANGE communication_id communication_id INT DEFAULT NULL, CHANGE message_id message_id VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE code code VARBINARY(8) DEFAULT \'NULL\', CHANGE prefix prefix VARCHAR(8) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE resource_external_id resource_external_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE pegass CHANGE identifier identifier VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE parent_identifier parent_identifier VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE phone CHANGE mobile mobile TINYINT(1) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE prefilled_answers CHANGE structure_id structure_id INT DEFAULT NULL, CHANGE colors colors LONGTEXT CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci` COMMENT \'(DC2Type:simple_array)\'');
        $this->addSql('ALTER TABLE report_repartition CHANGE structure_id structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure CHANGE parent_structure_id parent_structure_id INT DEFAULT NULL, CHANGE president president VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE last_pegass_update last_pegass_update DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE token CHANGE last_used_at last_used_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE twilio_call CHANGE error error VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE started_at started_at DATETIME DEFAULT \'NULL\', CHANGE ended_at ended_at DATETIME DEFAULT \'NULL\', CHANGE duration duration INT DEFAULT NULL, CHANGE from_number from_number VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sid sid VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE status status VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE price price VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE unit unit VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE twilio_message CHANGE error error VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE sid sid VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE status status VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE price price VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE unit unit VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE user CHANGE volunteer_id volunteer_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE volunteer CHANGE first_name first_name VARCHAR(80) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE last_name last_name VARCHAR(80) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE email email VARCHAR(80) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE birthday birthday DATETIME DEFAULT \'NULL\', CHANGE last_pegass_update last_pegass_update DATETIME DEFAULT \'NULL\', CHANGE optout_until optout_until DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE webhook CHANGE fallback_uri fallback_uri VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'NULL\' COLLATE `utf8mb4_unicode_ci`, CHANGE last_used_at last_used_at DATETIME DEFAULT \'NULL\'');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200510160632 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_structure (user_id VARCHAR(36) NOT NULL, structure_id INT NOT NULL, INDEX IDX_6FE1BA0EA76ED395 (user_id), INDEX IDX_6FE1BA0E2534008B (structure_id), PRIMARY KEY(user_id, structure_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user_structure ADD CONSTRAINT FK_6FE1BA0EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_structure ADD CONSTRAINT FK_6FE1BA0E2534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_information CHANGE is_developer is_developer TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE user ADD volunteer_id INT DEFAULT NULL, ADD locale VARCHAR(10) DEFAULT NULL, ADD nivol VARCHAR(80) DEFAULT NULL, ADD is_developer TINYINT(1) NOT NULL DEFAULT \'0\', ADD locked TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6498EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6498EFAB6B1 ON user (volunteer_id)');
        $this->addSql('CREATE INDEX nivol_idx ON user (nivol)');

        $this->addSql('
            INSERT IGNORE INTO user_structure
            SELECT u.id, uis.structure_id
            FROM user u 
            JOIN user_information ui ON ui.user_id = u.id
            JOIN user_information_structure uis ON uis.user_information_id = ui.id
        ');

        $this->addSql('
            UPDATE user u
            JOIN user_information ui ON ui.user_id = u.id
            SET u.volunteer_id = ui.volunteer_id,
                u.locale = ui.locale,
                u.nivol = ui.nivol,
                u.is_developer = ui.is_developer,
                u.locked = ui.locked
        ');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            INSERT IGNORE INTO user_information_structure
            SELECT ui.id, us.structure_id
            FROM user u 
            JOIN user_information ui ON ui.user_id = u.id
            JOIN user_structure us ON us.user_id = u.id
        ');

        $this->addSql('DROP TABLE user_structure');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6498EFAB6B1');
        $this->addSql('DROP INDEX UNIQ_8D93D6498EFAB6B1 ON user');
        $this->addSql('DROP INDEX nivol_idx ON user');
        $this->addSql('ALTER TABLE user DROP volunteer_id, DROP locale, DROP nivol, DROP is_developer, DROP locked');
        $this->addSql('ALTER TABLE user_information DROP INDEX UNIQ_8062D116A76ED395, ADD INDEX IDX_8062D116A76ED395 (user_id)');
        $this->addSql('ALTER TABLE user_information DROP INDEX UNIQ_8062D1168EFAB6B1, ADD INDEX IDX_8062D1168EFAB6B1 (volunteer_id)');
        $this->addSql('ALTER TABLE user_information CHANGE is_developer is_developer TINYINT(1) DEFAULT \'0\' NOT NULL');
    }
}

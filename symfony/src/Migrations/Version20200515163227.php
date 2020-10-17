<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200515163227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_information_structure DROP FOREIGN KEY FK_11897F164575EE58');
        $this->addSql('DROP TABLE user_information');
        $this->addSql('DROP TABLE user_information_structure');
        $this->addSql('ALTER TABLE user CHANGE is_developer is_developer TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE user_information (id INT AUTO_INCREMENT NOT NULL, user_id VARCHAR(36) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, volunteer_id INT DEFAULT NULL, locale VARCHAR(10) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, nivol VARCHAR(80) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, locked TINYINT(1) DEFAULT \'0\' NOT NULL, is_developer TINYINT(1) NOT NULL, INDEX nivol_idx (nivol), INDEX IDX_8062D116A76ED395 (user_id), INDEX IDX_8062D1168EFAB6B1 (volunteer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_information_structure (user_information_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_11897F164575EE58 (user_information_id), INDEX IDX_11897F162534008B (structure_id), PRIMARY KEY(user_information_id, structure_id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE user_information ADD CONSTRAINT FK_8062D1168EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('ALTER TABLE user_information ADD CONSTRAINT FK_8062D116A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_information_structure ADD CONSTRAINT FK_11897F162534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_information_structure ADD CONSTRAINT FK_11897F164575EE58 FOREIGN KEY (user_information_id) REFERENCES user_information (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user CHANGE is_developer is_developer TINYINT(1) NOT NULL');
    }
}

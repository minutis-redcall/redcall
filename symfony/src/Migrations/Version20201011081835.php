<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201011081835 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE badge (id INT AUTO_INCREMENT NOT NULL, category_id INT DEFAULT NULL, synonym_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, external_id VARCHAR(64) DEFAULT NULL, name VARCHAR(64) NOT NULL, description VARCHAR(255) DEFAULT NULL, priority INT NOT NULL DEFAULT 0, visibility TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_FEF0481D9F75D7B0 (external_id), INDEX IDX_FEF0481D12469DE2 (category_id), INDEX IDX_FEF0481D8C1B728E (synonym_id), INDEX IDX_FEF0481D727ACA70 (parent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE badge_visibility (badge_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_A6DDB802F7A2C2FC (badge_id), INDEX IDX_A6DDB8022534008B (structure_id), PRIMARY KEY(badge_id, structure_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE badge_restriction (badge_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_9460A919F7A2C2FC (badge_id), INDEX IDX_9460A9192534008B (structure_id), PRIMARY KEY(badge_id, structure_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE badge_volunteer (badge_id INT NOT NULL, volunteer_id INT NOT NULL, INDEX IDX_60EC814FF7A2C2FC (badge_id), INDEX IDX_60EC814F8EFAB6B1 (volunteer_id), PRIMARY KEY(badge_id, volunteer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, priority INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE badge ADD CONSTRAINT FK_FEF0481D12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE badge ADD CONSTRAINT FK_FEF0481D8C1B728E FOREIGN KEY (synonym_id) REFERENCES badge (id)');
        $this->addSql('ALTER TABLE badge ADD CONSTRAINT FK_FEF0481D727ACA70 FOREIGN KEY (parent_id) REFERENCES badge (id)');
        $this->addSql('ALTER TABLE badge_visibility ADD CONSTRAINT FK_A6DDB802F7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id)');
        $this->addSql('ALTER TABLE badge_visibility ADD CONSTRAINT FK_A6DDB8022534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE badge_restriction ADD CONSTRAINT FK_9460A919F7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id)');
        $this->addSql('ALTER TABLE badge_restriction ADD CONSTRAINT FK_9460A9192534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('ALTER TABLE badge_volunteer ADD CONSTRAINT FK_60EC814FF7A2C2FC FOREIGN KEY (badge_id) REFERENCES badge (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE badge_volunteer ADD CONSTRAINT FK_60EC814F8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE badge DROP FOREIGN KEY FK_FEF0481D8C1B728E');
        $this->addSql('ALTER TABLE badge DROP FOREIGN KEY FK_FEF0481D727ACA70');
        $this->addSql('ALTER TABLE badge_visibility DROP FOREIGN KEY FK_A6DDB802F7A2C2FC');
        $this->addSql('ALTER TABLE badge_restriction DROP FOREIGN KEY FK_9460A919F7A2C2FC');
        $this->addSql('ALTER TABLE badge_volunteer DROP FOREIGN KEY FK_60EC814FF7A2C2FC');
        $this->addSql('ALTER TABLE badge DROP FOREIGN KEY FK_FEF0481D12469DE2');
        $this->addSql('DROP TABLE badge');
        $this->addSql('DROP TABLE badge_visibility');
        $this->addSql('DROP TABLE badge_restriction');
        $this->addSql('DROP TABLE badge_volunteer');
        $this->addSql('DROP TABLE category');
    }
}

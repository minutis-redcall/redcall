<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260112175829 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE volunteer_group (id INT AUTO_INCREMENT NOT NULL, campaign_id INT NOT NULL, volunteer_id INT NOT NULL, group_index INT NOT NULL, INDEX campaign_idx (campaign_id), INDEX volunteer_idx (volunteer_id), UNIQUE INDEX unique_assignment (campaign_id, volunteer_id, group_index), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE volunteer_group ADD CONSTRAINT FK_5FAE9937F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE volunteer_group ADD CONSTRAINT FK_5FAE99378EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE volunteer_group');
    }
}

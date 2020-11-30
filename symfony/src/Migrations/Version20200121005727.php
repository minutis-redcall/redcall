<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200121005727 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE campaign_structure (campaign_id INT NOT NULL, structure_id INT NOT NULL, INDEX IDX_AF892B91F639F774 (campaign_id), INDEX IDX_AF892B912534008B (structure_id), PRIMARY KEY(campaign_id, structure_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $paris0102 = $this->connection->fetchColumn('SELECT id FROM structure WHERE identifier = :identifier', [
            'identifier' => '889',
        ]);

        $this->addSql('
            INSERT INTO campaign_structure (campaign_id, structure_id)
            SELECT c.id as campaign_id, :paris_0102 as structure_id
            FROM campaign c
        ', [
            'paris_0102' => $paris0102,
        ]);

        $this->addSql('ALTER TABLE campaign_structure ADD CONSTRAINT FK_AF892B91F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign_structure ADD CONSTRAINT FK_AF892B912534008B FOREIGN KEY (structure_id) REFERENCES structure (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE campaign_structure');
    }
}

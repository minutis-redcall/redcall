<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200421205922 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $structureId = $this->connection->fetchColumn('SELECT id FROM structure WHERE identifier = 0');

        if (false === $structureId) {
            return;
        }

        $this->addSql('DELETE FROM user_information_structure WHERE structure_id = :id', [
            'id' => $structureId,
        ]);

        $this->addSql('DELETE FROM volunteer_structure WHERE structure_id = :id', [
            'id' => $structureId,
        ]);

        $this->addSql('UPDATE structure SET enabled = 0 WHERE id = :id', [
            'id' => $structureId,
        ]);

        $this->addSql('UPDATE structure SET identifier = NULL WHERE identifier > 10000000');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}

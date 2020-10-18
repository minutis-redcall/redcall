<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190616024339 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql("
            INSERT IGNORE INTO `tag`
            VALUES 
                   (1,'emergency_assistance'),
                   (2,'social_assistance'),
                   (3,'psc_1'),
                   (4,'pse_1'),
                   (6,'pse_2'),
                   (8,'drvr_vl'),
                   (9,'drvr_vpsp'),
                   (10,'ci')
        ");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DELETE FROM tag WHERE id IN (1,2,3,4,6,8,9,10)');
    }
}

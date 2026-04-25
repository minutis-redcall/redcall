<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add last_activity_at to campaign for optimized polling hash';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE campaign ADD last_activity_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX last_activity_idx ON campaign (last_activity_at)');

        // Backfill from communication.last_activity_at
        $this->addSql('
            UPDATE campaign c
            SET c.last_activity_at = (
                SELECT MAX(co.last_activity_at)
                FROM communication co
                WHERE co.campaign_id = c.id
            )
        ');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('DROP INDEX last_activity_idx ON campaign');
        $this->addSql('ALTER TABLE campaign DROP last_activity_at');
    }
}

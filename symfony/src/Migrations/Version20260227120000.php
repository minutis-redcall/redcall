<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260227120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Remove platform column from all entities (only FR was used)';
    }

    public function up(Schema $schema) : void
    {
        // Drop unique constraints that include platform, then re-create without it
        $this->addSql('ALTER TABLE volunteer DROP INDEX pf_extid_idx');
        $this->addSql('CREATE UNIQUE INDEX extid_idx ON volunteer (external_id)');

        $this->addSql('ALTER TABLE structure DROP INDEX pf_extid_idx');
        $this->addSql('CREATE UNIQUE INDEX extid_idx ON structure (external_id)');

        $this->addSql('ALTER TABLE badge DROP INDEX pf_extid_idx');
        $this->addSql('CREATE UNIQUE INDEX extid_idx ON badge (external_id)');

        $this->addSql('ALTER TABLE category DROP INDEX pf_extid_idx');
        $this->addSql('CREATE UNIQUE INDEX extid_idx ON category (external_id)');

        // Drop platform indexes
        $this->addSql('ALTER TABLE user DROP INDEX platform_idx');
        $this->addSql('ALTER TABLE campaign DROP INDEX platformx');
        $this->addSql('ALTER TABLE prefilled_answers DROP INDEX platformx');

        // Delete non-FR data if any (should be none, but just in case)
        $this->addSql("DELETE FROM user WHERE platform != 'FR'");
        $this->addSql("DELETE FROM volunteer WHERE platform != 'FR'");
        $this->addSql("DELETE FROM structure WHERE platform != 'FR'");
        $this->addSql("DELETE FROM campaign WHERE platform != 'FR'");
        $this->addSql("DELETE FROM badge WHERE platform != 'FR'");
        $this->addSql("DELETE FROM category WHERE platform != 'FR'");
        $this->addSql("DELETE FROM prefilled_answers WHERE platform != 'FR'");

        // Drop platform columns
        $this->addSql('ALTER TABLE user DROP COLUMN platform');
        $this->addSql('ALTER TABLE volunteer DROP COLUMN platform');
        $this->addSql('ALTER TABLE structure DROP COLUMN platform');
        $this->addSql('ALTER TABLE campaign DROP COLUMN platform');
        $this->addSql('ALTER TABLE badge DROP COLUMN platform');
        $this->addSql('ALTER TABLE category DROP COLUMN platform');
        $this->addSql('ALTER TABLE prefilled_answers DROP COLUMN platform');
    }

    public function down(Schema $schema) : void
    {
        // Add platform columns back
        $this->addSql("ALTER TABLE user ADD platform VARCHAR(5) NOT NULL DEFAULT 'FR'");
        $this->addSql("ALTER TABLE volunteer ADD platform VARCHAR(5) NOT NULL DEFAULT 'FR'");
        $this->addSql("ALTER TABLE structure ADD platform VARCHAR(5) NOT NULL DEFAULT 'FR'");
        $this->addSql("ALTER TABLE campaign ADD platform VARCHAR(5) NOT NULL DEFAULT 'FR'");
        $this->addSql("ALTER TABLE badge ADD platform VARCHAR(5) NOT NULL DEFAULT 'FR'");
        $this->addSql("ALTER TABLE category ADD platform VARCHAR(5) NOT NULL DEFAULT 'FR'");
        $this->addSql("ALTER TABLE prefilled_answers ADD platform VARCHAR(5) NOT NULL DEFAULT 'FR'");

        // Restore platform indexes
        $this->addSql('CREATE INDEX platform_idx ON user (platform)');
        $this->addSql('CREATE INDEX platformx ON campaign (platform)');
        $this->addSql('CREATE INDEX platformx ON prefilled_answers (platform)');

        // Restore unique constraints with platform
        $this->addSql('ALTER TABLE volunteer DROP INDEX extid_idx');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON volunteer (platform, external_id)');

        $this->addSql('ALTER TABLE structure DROP INDEX extid_idx');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON structure (platform, external_id)');

        $this->addSql('ALTER TABLE badge DROP INDEX extid_idx');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON badge (platform, external_id)');

        $this->addSql('ALTER TABLE category DROP INDEX extid_idx');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON category (platform, external_id)');
    }
}

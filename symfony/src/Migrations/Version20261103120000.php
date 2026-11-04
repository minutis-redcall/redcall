<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Attempt counter on expirables, used to cap NIVOL login code guesses
 * (brute-force protection, CERT-FR report RM #1080870).
 */
final class Version20261103120000 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Add expirable.attempts to cap NIVOL login code guesses';
    }

    public function up(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE expirable ADD attempts INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        $this->addSql('ALTER TABLE expirable DROP attempts');
    }
}

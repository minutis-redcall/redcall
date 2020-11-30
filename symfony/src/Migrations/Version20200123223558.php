<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200123223558 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE message CHANGE prefix prefix VARCHAR(8) NOT NULL');
        $this->addSql('CREATE INDEX prefixx ON message (prefix)');

        $prefixes = [];
        $updates  = [];
        $messages = $this->connection->fetchAll('SELECT id, volunteer_id FROM message');

        if (!$messages) {
            return;
        }

        foreach ($messages as $message) {
            if (!array_key_exists($message['volunteer_id'], $prefixes)) {
                $prefixes[$message['volunteer_id']] = 'A';
            }

            $updates[$message['id']] = $prefixes[$message['volunteer_id']];
            $prefixes[$message['volunteer_id']]++;
        }

        $parameters = [];
        $sql        = 'UPDATE message SET prefix = CASE ';
        foreach ($updates as $id => $prefix) {
            $sql          .= ' WHEN id = ? THEN ? ';
            $parameters[] = $id;
            $parameters[] = $prefix;
        }
        $sql .= ' END';

        $this->addSql($sql, $parameters);
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX prefixx ON message');
        $this->addSql('ALTER TABLE message CHANGE prefix prefix VARCHAR(8) CHARACTER SET utf8mb4 DEFAULT \'A\' NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}

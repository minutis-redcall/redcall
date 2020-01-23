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
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE message CHANGE prefix prefix VARCHAR(8) NOT NULL');
        $this->addSql('CREATE INDEX prefixx ON message (prefix)');

        $messages = $this->connection->fetchAll('SELECT id, volunteer_id FROM message');
        foreach ($messages as $message) {
            $prefix = 'A';

            do {
                $test = $this->connection->fetchColumn('
                    SELECT m.id
                    FROM message m
                    JOIN communication co on m.communication_id = co.id
                    JOIN campaign ca ON co.campaign_id = ca.id
                    WHERE ca.active = 1
                    AND m.volunteer_id = :volunteer_id
                    AND m.prefix = :prefix
                ', [
                    'volunteer_id' => $message['volunteer_id'],
                    'prefix'       => $prefix,
                ]);

                if (!$test) {
                    break;
                }

                $prefix++;
            } while (true);

            $this->connection->update('message', [
                'prefix' => $prefix,
            ], [
                'id' => $message['id'],
            ]);
        }

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX prefixx ON message');
        $this->addSql('ALTER TABLE message CHANGE prefix prefix VARCHAR(8) CHARACTER SET utf8mb4 DEFAULT \'A\' NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}

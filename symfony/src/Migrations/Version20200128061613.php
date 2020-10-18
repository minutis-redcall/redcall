<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200128061613 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX prefixx ON message');
        $this->addSql('CREATE INDEX prefixx ON message (volunteer_id, prefix)');
        $this->addSql('DROP INDEX lastpegassupdatex ON volunteer');
        $this->addSql('CREATE INDEX phone_numberx ON volunteer (phone_number)');
        $this->addSql('CREATE INDEX emailx ON volunteer (email)');
        $this->addSql('CREATE INDEX enabledx ON volunteer (id, enabled)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX prefixx ON message');
        $this->addSql('CREATE INDEX prefixx ON message (prefix)');
        $this->addSql('DROP INDEX phone_numberx ON volunteer');
        $this->addSql('DROP INDEX emailx ON volunteer');
        $this->addSql('DROP INDEX enabledx ON volunteer');
        $this->addSql('CREATE INDEX lastpegassupdatex ON volunteer (last_pegass_update)');
    }
}

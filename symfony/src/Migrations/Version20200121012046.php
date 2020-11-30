<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200121012046 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_information ADD volunteer_id INT DEFAULT NULL AFTER nivol');
        $this->addSql('ALTER TABLE user_information ADD CONSTRAINT FK_8062D1168EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');
        $this->addSql('CREATE INDEX IDX_8062D1168EFAB6B1 ON user_information (volunteer_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_information DROP FOREIGN KEY FK_8062D1168EFAB6B1');
        $this->addSql('DROP INDEX IDX_8062D1168EFAB6B1 ON user_information');
        $this->addSql('ALTER TABLE user_information DROP volunteer_id');
    }
}

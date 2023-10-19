<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231019132511 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE phone_volunteer (phone_id INT NOT NULL, volunteer_id INT NOT NULL, INDEX IDX_583ED5E33B7323CB (phone_id), INDEX IDX_583ED5E38EFAB6B1 (volunteer_id), PRIMARY KEY(phone_id, volunteer_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE phone_volunteer ADD CONSTRAINT FK_583ED5E33B7323CB FOREIGN KEY (phone_id) REFERENCES phone (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE phone_volunteer ADD CONSTRAINT FK_583ED5E38EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON DELETE CASCADE');
        $this->addSql('INSERT INTO phone_volunteer (phone_id, volunteer_id) SELECT id, volunteer_id FROM phone');
        $this->addSql('ALTER TABLE phone DROP FOREIGN KEY FK_444F97DD8EFAB6B1');
        $this->addSql('DROP INDEX IDX_444F97DD8EFAB6B1 ON phone');
        $this->addSql('ALTER TABLE phone DROP volunteer_id');
        $this->addSql('ALTER TABLE volunteer CHANGE minor minor TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE phone ADD volunteer_id INT NOT NULL');
        $this->addSql('UPDATE phone JOIN volunteer ON phone.id = phone_id SET phone.volunteer_id = volunteer.id');
        $this->addSql('ALTER TABLE phone ADD CONSTRAINT FK_444F97DD8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_444F97DD8EFAB6B1 ON phone (volunteer_id)');
        $this->addSql('ALTER TABLE volunteer CHANGE minor minor TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('DROP TABLE phone_volunteer');
    }
}

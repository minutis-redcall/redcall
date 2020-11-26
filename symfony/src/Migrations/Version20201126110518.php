<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20201126110518 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE phone (id INT AUTO_INCREMENT NOT NULL, volunteer_id INT NOT NULL, is_preferred TINYINT(1) NOT NULL, country_code VARCHAR(2) NOT NULL, prefix SMALLINT NOT NULL, e164 VARCHAR(32) NOT NULL UNIQUE, national VARCHAR(32) NOT NULL, international VARCHAR(32) NOT NULL, INDEX IDX_444F97DD8EFAB6B1 (volunteer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE phone ADD CONSTRAINT FK_444F97DD8EFAB6B1 FOREIGN KEY (volunteer_id) REFERENCES volunteer (id)');

        $this->addSql('
            INSERT IGNORE INTO phone
            SELECT id,
                   id as volunteer_id,
                   1 as is_preferred,
                   "FR" as country_code,
                   33 as prefix,
                   CONCAT("+", phone_number) as e164,
                   CONCAT(
                       "0", 
                       SUBSTRING(phone_number, 3, 1), 
                       " ", 
                       SUBSTRING(phone_number, 4, 2), 
                       " ", 
                       SUBSTRING(phone_number, 6, 2), 
                       " ", 
                       SUBSTRING(phone_number, 8, 2),
                       " ", 
                       SUBSTRING(phone_number, 10, 2)
                   ) as national,
                   CONCAT(
                       "+33", 
                       SUBSTRING(phone_number, 3, 1), 
                       " ", 
                       SUBSTRING(phone_number, 4, 2), 
                       " ", 
                       SUBSTRING(phone_number, 6, 2), 
                       " ", 
                       SUBSTRING(phone_number, 8, 2),
                       " ", 
                       SUBSTRING(phone_number, 10, 2)
                   ) as international
            FROM volunteer
            WHERE phone_number IS NOT NULL
            AND phone_number <> ""
            AND phone_number <> "33600000000"
        ');

        $this->addSql('ALTER TABLE volunteer DROP COLUMN phone_number');

        $this->addSql('UPDATE twilio_message SET from_number = CONCAT("+", from_number)');
        $this->addSql('UPDATE twilio_message SET to_number = CONCAT("+", to_number)');
        $this->addSql('UPDATE twilio_call SET from_number = CONCAT("+", from_number)');
        $this->addSql('UPDATE twilio_call SET to_number = CONCAT("+", to_number)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer ADD COLUMN phone_number VARCHAR(32) NULL DEFAULT NULL');

        $this->addSql('
            UPDATE volunteer v
            JOIN phone p ON v.id = p.volunteer_id
            SET v.phone_number = SUBSTRING(p.e164, 2)
        ');

        $this->addSql('DROP TABLE phone');

        $this->addSql('UPDATE twilio_message SET from_number = SUBSTR("from_number", 2)');
        $this->addSql('UPDATE twilio_message SET to_number = SUBSTR("to_number", 2)');
        $this->addSql('UPDATE twilio_call SET from_number = SUBSTR("from_number", 2)');
        $this->addSql('UPDATE twilio_call SET to_number = SUBSTR("to_number", 2)');
    }
}

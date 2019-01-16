<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181029184924 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            UPDATE campaign
            SET type = CASE
              WHEN type = "green" THEN "1_green" 
              WHEN type = "light_orange" THEN "2_light_orange" 
              WHEN type = "dark_orange" THEN "3_dark_orange" 
              WHEN type = "red" THEN "4_red"
              ELSE "1_green"  
            END
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('
            UPDATE campaign
            SET type = CASE
              WHEN type = "1_green" THEN "green" 
              WHEN type = "2_light_orange" THEN "light_orange" 
              WHEN type = "3_dark_orange" THEN "dark_orange" 
              WHEN type = "4_red" THEN "red"
              ELSE "green"  
            END
        ');
    }
}

<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181105010112 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer_import ADD tags TEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', DROP has_psc1, DROP has_pse1, DROP has_pse1r, DROP has_pse2, DROP has_pse2r, DROP has_drvr_vl, DROP has_drvr_vpsp, DROP has_ci, DROP has_ci_r');
        $this->addSql("UPDATE tag SET label = 'emergency_assistance' WHERE id = 1");
        $this->addSql("UPDATE tag SET label = 'social_assistance' WHERE id = 2");
        $this->addSql("UPDATE tag SET label = 'psc_1' WHERE id = 3");
        $this->addSql("UPDATE tag SET label = 'pse_1_i' WHERE id = 4");
        $this->addSql("UPDATE tag SET label = 'pse_1_r' WHERE id = 5");
        $this->addSql("UPDATE tag SET label = 'pse_2_i' WHERE id = 6");
        $this->addSql("UPDATE tag SET label = 'pse_2_r' WHERE id = 7");
        $this->addSql("UPDATE tag SET label = 'drvr_vl' WHERE id = 8");
        $this->addSql("UPDATE tag SET label = 'drvr_vpsp' WHERE id = 9");
        $this->addSql("UPDATE tag SET label = 'ci_i' WHERE id = 10");
        $this->addSql("UPDATE tag SET label = 'ci_r' WHERE id = 11");
        $this->addSql("DELETE FROM tag WHERE id > 11");
        $this->addSql('ALTER TABLE volunteer_import CHANGE is_minor is_minor TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer_import CHANGE is_minor is_minor TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE volunteer_import ADD has_psc1 TINYINT(1) NOT NULL, ADD has_pse1 TINYINT(1) NOT NULL, ADD has_pse1r TINYINT(1) NOT NULL, ADD has_pse2 TINYINT(1) NOT NULL, ADD has_pse2r TINYINT(1) NOT NULL, ADD has_drvr_vl TINYINT(1) NOT NULL, ADD has_drvr_vpsp TINYINT(1) NOT NULL, ADD has_ci TINYINT(1) NOT NULL, ADD has_ci_r TINYINT(1) NOT NULL, DROP tags');
    }
}

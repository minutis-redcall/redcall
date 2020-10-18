<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180619050127 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE email_verification (username VARCHAR(64) NOT NULL, uuid VARCHAR(36) NOT NULL, timestamp INT UNSIGNED NOT NULL, UNIQUE INDEX uuid_idx (uuid), PRIMARY KEY(username)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE password_recovery (username VARCHAR(64) NOT NULL, uuid VARCHAR(36) NOT NULL, timestamp INT UNSIGNED NOT NULL, UNIQUE INDEX uuid_idx (uuid), PRIMARY KEY(username)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (username VARCHAR(64) NOT NULL, password VARCHAR(72) NOT NULL, is_verified TINYINT(1) NOT NULL, is_trusted TINYINT(1) NOT NULL, is_admin TINYINT(1) NOT NULL, PRIMARY KEY(username)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE whitelist (ip INT NOT NULL, timestamp INT UNSIGNED NOT NULL, PRIMARY KEY(ip)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE email_verification');
        $this->addSql('DROP TABLE password_recovery');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE whitelist');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Entity\Phone;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210115155124 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        // Same as Version20210109191432 but without the bug
        $rows      = $this->connection->fetchAll('SELECT id, e164 FROM phone');
        $phoneUtil = PhoneNumberUtil::getInstance();

        $query = 'UPDATE phone SET mobile = CASE ';

        foreach ($rows as $row) {
            $parsed = $phoneUtil->parse($row['e164'], Phone::DEFAULT_LANG);
            $query  .= sprintf(' WHEN id = %d THEN %d ', $row['id'], PhoneNumberType::MOBILE === $phoneUtil->getNumberType($parsed));
        }

        $query .= 'END';

        $this->addSql($query);
    }

    public function down(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');
    }
}

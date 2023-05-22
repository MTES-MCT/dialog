<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522140713 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period ADD include_holidays BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE period DROP applicable_months');
        $this->addSql('ALTER TABLE period DROP special_days');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period ADD applicable_months TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD special_days TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE period DROP include_holidays');
        $this->addSql('COMMENT ON COLUMN period.applicable_months IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN period.special_days IS \'(DC2Type:array)\'');
    }
}

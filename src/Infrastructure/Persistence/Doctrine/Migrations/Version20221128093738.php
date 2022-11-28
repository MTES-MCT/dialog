<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221128093738 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE special_day (uuid UUID NOT NULL, period_uuid UUID NOT NULL, type VARCHAR(30) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_BA55C3C61779DA08 ON special_day (period_uuid)');
        $this->addSql('ALTER TABLE special_day ADD CONSTRAINT FK_BA55C3C61779DA08 FOREIGN KEY (period_uuid) REFERENCES period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX uniq_62a51e671779da08');
        $this->addSql('CREATE INDEX IDX_62A51E671779DA08 ON time_period_of_day (period_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE special_day DROP CONSTRAINT FK_BA55C3C61779DA08');
        $this->addSql('DROP TABLE special_day');
        $this->addSql('DROP INDEX IDX_62A51E671779DA08');
        $this->addSql('CREATE UNIQUE INDEX uniq_62a51e671779da08 ON time_period_of_day (period_uuid)');
    }
}

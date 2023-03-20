<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230315135642 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE special_day DROP CONSTRAINT fk_ba55c3c61779da08');
        $this->addSql('ALTER TABLE time_period_of_day DROP CONSTRAINT fk_62a51e671779da08');
        $this->addSql('ALTER TABLE day_week_month DROP CONSTRAINT fk_639d595a1779da08');
        $this->addSql('DROP TABLE special_day');
        $this->addSql('DROP TABLE time_period_of_day');
        $this->addSql('DROP TABLE day_week_month');
        $this->addSql('ALTER TABLE period ADD day_start_time TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD day_end_time TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD applicable_days TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD applicable_months TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD special_days TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE period DROP name');
        $this->addSql('ALTER TABLE period DROP start_date');
        $this->addSql('ALTER TABLE period DROP end_date');
        $this->addSql('COMMENT ON COLUMN period.day_start_time IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN period.day_end_time IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN period.applicable_days IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN period.applicable_months IS \'(DC2Type:array)\'');
        $this->addSql('COMMENT ON COLUMN period.special_days IS \'(DC2Type:array)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE special_day (uuid UUID NOT NULL, period_uuid UUID NOT NULL, type VARCHAR(30) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX idx_ba55c3c61779da08 ON special_day (period_uuid)');
        $this->addSql('CREATE TABLE time_period_of_day (uuid UUID NOT NULL, period_uuid UUID NOT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX idx_62a51e671779da08 ON time_period_of_day (period_uuid)');
        $this->addSql('COMMENT ON COLUMN time_period_of_day.start_time IS \'(DC2Type:time_immutable)\'');
        $this->addSql('COMMENT ON COLUMN time_period_of_day.end_time IS \'(DC2Type:time_immutable)\'');
        $this->addSql('CREATE TABLE day_week_month (uuid UUID NOT NULL, period_uuid UUID NOT NULL, applicable_day VARCHAR(10) DEFAULT NULL, applicable_month VARCHAR(10) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX idx_639d595a1779da08 ON day_week_month (period_uuid)');
        $this->addSql('ALTER TABLE special_day ADD CONSTRAINT fk_ba55c3c61779da08 FOREIGN KEY (period_uuid) REFERENCES period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE time_period_of_day ADD CONSTRAINT fk_62a51e671779da08 FOREIGN KEY (period_uuid) REFERENCES period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE day_week_month ADD CONSTRAINT fk_639d595a1779da08 FOREIGN KEY (period_uuid) REFERENCES period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE period ADD name VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD start_date TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD end_date TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period DROP day_start_time');
        $this->addSql('ALTER TABLE period DROP day_end_time');
        $this->addSql('ALTER TABLE period DROP applicable_days');
        $this->addSql('ALTER TABLE period DROP applicable_months');
        $this->addSql('ALTER TABLE period DROP special_days');
    }
}

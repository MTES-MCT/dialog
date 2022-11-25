<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221125150431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE overall_period (uuid UUID NOT NULL, regulation_condition_uuid UUID NOT NULL, start_period TIMESTAMP(0) WITH TIME ZONE NOT NULL, end_period TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A58D9F529F073263 ON overall_period (regulation_condition_uuid)');
        $this->addSql('COMMENT ON COLUMN overall_period.start_period IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN overall_period.end_period IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('CREATE TABLE period (uuid UUID NOT NULL, overall_valid_period_uuid UUID DEFAULT NULL, overall_exception_period_uuid UUID DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, start_date TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, end_date TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_C5B81ECE2ADA3265 ON period (overall_valid_period_uuid)');
        $this->addSql('CREATE INDEX IDX_C5B81ECE1276C856 ON period (overall_exception_period_uuid)');
        $this->addSql('COMMENT ON COLUMN period.start_date IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN period.end_date IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('CREATE TABLE time_period_of_day (uuid UUID NOT NULL, period_uuid UUID NOT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_62A51E671779DA08 ON time_period_of_day (period_uuid)');
        $this->addSql('COMMENT ON COLUMN time_period_of_day.start_time IS \'(DC2Type:time_immutable)\'');
        $this->addSql('COMMENT ON COLUMN time_period_of_day.end_time IS \'(DC2Type:time_immutable)\'');
        $this->addSql('ALTER TABLE overall_period ADD CONSTRAINT FK_A58D9F529F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE2ADA3265 FOREIGN KEY (overall_valid_period_uuid) REFERENCES overall_period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE1276C856 FOREIGN KEY (overall_exception_period_uuid) REFERENCES overall_period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE time_period_of_day ADD CONSTRAINT FK_62A51E671779DA08 FOREIGN KEY (period_uuid) REFERENCES period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT fk_9d8762b7aaee03d4');
        $this->addSql('DROP INDEX uniq_9d8762b7aaee03d4');
        $this->addSql('ALTER TABLE regulation_condition DROP vehicle_characteristics_uuid');
        $this->addSql('ALTER TABLE vehicle_characteristics ADD regulation_condition_uuid UUID NOT NULL');
        $this->addSql('ALTER TABLE vehicle_characteristics ADD CONSTRAINT FK_54F8F40A9F073263 FOREIGN KEY (regulation_condition_uuid) REFERENCES regulation_condition (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_54F8F40A9F073263 ON vehicle_characteristics (regulation_condition_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE overall_period DROP CONSTRAINT FK_A58D9F529F073263');
        $this->addSql('ALTER TABLE period DROP CONSTRAINT FK_C5B81ECE2ADA3265');
        $this->addSql('ALTER TABLE period DROP CONSTRAINT FK_C5B81ECE1276C856');
        $this->addSql('ALTER TABLE time_period_of_day DROP CONSTRAINT FK_62A51E671779DA08');
        $this->addSql('DROP TABLE overall_period');
        $this->addSql('DROP TABLE period');
        $this->addSql('DROP TABLE time_period_of_day');
        $this->addSql('ALTER TABLE vehicle_characteristics DROP CONSTRAINT FK_54F8F40A9F073263');
        $this->addSql('DROP INDEX UNIQ_54F8F40A9F073263');
        $this->addSql('ALTER TABLE vehicle_characteristics DROP regulation_condition_uuid');
        $this->addSql('ALTER TABLE regulation_condition ADD vehicle_characteristics_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT fk_9d8762b7aaee03d4 FOREIGN KEY (vehicle_characteristics_uuid) REFERENCES vehicle_characteristics (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_9d8762b7aaee03d4 ON regulation_condition (vehicle_characteristics_uuid)');
    }
}

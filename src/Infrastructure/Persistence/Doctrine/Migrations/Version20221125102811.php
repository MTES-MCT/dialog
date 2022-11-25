<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221125102811 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE period (uuid UUID NOT NULL, overall_valid_period_uuid UUID DEFAULT NULL, overall_exception_period_uuid UUID DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, start_date TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, end_date TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_C5B81ECE2ADA3265 ON period (overall_valid_period_uuid)');
        $this->addSql('CREATE INDEX IDX_C5B81ECE1276C856 ON period (overall_exception_period_uuid)');
        $this->addSql('COMMENT ON COLUMN period.start_date IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN period.end_date IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE2ADA3265 FOREIGN KEY (overall_valid_period_uuid) REFERENCES overall_period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE period ADD CONSTRAINT FK_C5B81ECE1276C856 FOREIGN KEY (overall_exception_period_uuid) REFERENCES overall_period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE period DROP CONSTRAINT FK_C5B81ECE2ADA3265');
        $this->addSql('ALTER TABLE period DROP CONSTRAINT FK_C5B81ECE1276C856');
        $this->addSql('DROP TABLE period');
    }
}

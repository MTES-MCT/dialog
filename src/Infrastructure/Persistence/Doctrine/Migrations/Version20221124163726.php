<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221124163726 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE overall_period (uuid UUID NOT NULL, start_period TIMESTAMP(0) WITH TIME ZONE NOT NULL, end_period TIMESTAMP(0) WITH TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('COMMENT ON COLUMN overall_period.start_period IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN overall_period.end_period IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('ALTER TABLE regulation_condition ADD overall_period_uuid UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE regulation_condition ADD CONSTRAINT FK_9D8762B711378D9B FOREIGN KEY (overall_period_uuid) REFERENCES overall_period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9D8762B711378D9B ON regulation_condition (overall_period_uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE regulation_condition DROP CONSTRAINT FK_9D8762B711378D9B');
        $this->addSql('DROP TABLE overall_period');
        $this->addSql('DROP INDEX UNIQ_9D8762B711378D9B');
        $this->addSql('ALTER TABLE regulation_condition DROP overall_period_uuid');
    }
}

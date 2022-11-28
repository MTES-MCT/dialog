<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221128103250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE day_week_month (uuid UUID NOT NULL, period_uuid UUID NOT NULL, applicable_day VARCHAR(10) DEFAULT NULL, applicable_month VARCHAR(10) DEFAULT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_639D595A1779DA08 ON day_week_month (period_uuid)');
        $this->addSql('ALTER TABLE day_week_month ADD CONSTRAINT FK_639D595A1779DA08 FOREIGN KEY (period_uuid) REFERENCES period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE day_week_month DROP CONSTRAINT FK_639D595A1779DA08');
        $this->addSql('DROP TABLE day_week_month');
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221125143332 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE time_period_of_day (uuid UUID NOT NULL, period_uuid UUID NOT NULL, start_time TIME(0) WITHOUT TIME ZONE NOT NULL, end_time TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_62A51E671779DA08 ON time_period_of_day (period_uuid)');
        $this->addSql('COMMENT ON COLUMN time_period_of_day.start_time IS \'(DC2Type:time_immutable)\'');
        $this->addSql('COMMENT ON COLUMN time_period_of_day.end_time IS \'(DC2Type:time_immutable)\'');
        $this->addSql('ALTER TABLE time_period_of_day ADD CONSTRAINT FK_62A51E671779DA08 FOREIGN KEY (period_uuid) REFERENCES period (uuid) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE time_period_of_day DROP CONSTRAINT FK_62A51E671779DA08');
        $this->addSql('DROP TABLE time_period_of_day');
    }
}

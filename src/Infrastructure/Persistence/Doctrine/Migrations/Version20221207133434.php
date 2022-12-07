<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221207133434 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE overall_period ALTER start_period TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE overall_period ALTER end_period TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE overall_period ALTER end_period DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN overall_period.start_period IS NULL');
        $this->addSql('COMMENT ON COLUMN overall_period.end_period IS NULL');
        $this->addSql('ALTER TABLE period ALTER start_date TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE period ALTER end_date TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('COMMENT ON COLUMN period.start_date IS NULL');
        $this->addSql('COMMENT ON COLUMN period.end_date IS NULL');
        $this->addSql('ALTER TABLE regulation_order_record ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('COMMENT ON COLUMN regulation_order_record.created_at IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE regulation_order_record ALTER created_at TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('COMMENT ON COLUMN regulation_order_record.created_at IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('ALTER TABLE overall_period ALTER start_period TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE overall_period ALTER end_period TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE overall_period ALTER end_period SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN overall_period.start_period IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN overall_period.end_period IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('ALTER TABLE period ALTER start_date TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('ALTER TABLE period ALTER end_date TYPE TIMESTAMP(0) WITH TIME ZONE');
        $this->addSql('COMMENT ON COLUMN period.start_date IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN period.end_date IS \'(DC2Type:datetimetz_immutable)\'');
    }
}

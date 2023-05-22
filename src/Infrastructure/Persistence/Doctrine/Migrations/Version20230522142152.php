<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230522142152 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period ADD start_time TIMESTAMP(0) WITH TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE period ADD end_time TIMESTAMP(0) WITH TIME ZONE NOT NULL');
        $this->addSql('ALTER TABLE period DROP day_start_time');
        $this->addSql('ALTER TABLE period DROP day_end_time');
        $this->addSql('ALTER TABLE period ALTER applicable_days SET NOT NULL');
        $this->addSql('COMMENT ON COLUMN period.start_time IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN period.end_time IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE period ADD day_start_time TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period ADD day_end_time TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE period DROP start_time');
        $this->addSql('ALTER TABLE period DROP end_time');
        $this->addSql('ALTER TABLE period ALTER applicable_days DROP NOT NULL');
        $this->addSql('COMMENT ON COLUMN period.day_start_time IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN period.day_end_time IS \'(DC2Type:datetimetz_immutable)\'');
    }
}

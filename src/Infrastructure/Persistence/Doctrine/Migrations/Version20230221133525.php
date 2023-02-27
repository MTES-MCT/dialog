<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230221133525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE overall_period ADD start_time TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE overall_period ADD end_time TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE overall_period RENAME COLUMN start_period TO start_date');
        $this->addSql('ALTER TABLE overall_period RENAME COLUMN end_period TO end_date');
        $this->addSql('COMMENT ON COLUMN overall_period.start_time IS \'(DC2Type:datetimetz_immutable)\'');
        $this->addSql('COMMENT ON COLUMN overall_period.end_time IS \'(DC2Type:datetimetz_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE overall_period DROP start_time');
        $this->addSql('ALTER TABLE overall_period DROP end_time');
        $this->addSql('ALTER TABLE overall_period RENAME COLUMN start_date TO start_period');
        $this->addSql('ALTER TABLE overall_period RENAME COLUMN end_date TO end_period');
    }
}

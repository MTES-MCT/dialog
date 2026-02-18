<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260218120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add IGN report tracking fields to report_address (ign_report_id, ign_report_status, ign_status_updated_at)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE report_address ADD ign_report_id VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE report_address ADD ign_report_status VARCHAR(64) DEFAULT NULL');
        $this->addSql('ALTER TABLE report_address ADD ign_status_updated_at TIMESTAMP(0) WITH TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE report_address DROP ign_report_id');
        $this->addSql('ALTER TABLE report_address DROP ign_report_status');
        $this->addSql('ALTER TABLE report_address DROP ign_status_updated_at');
    }
}

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\MetabaseMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260129110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create analytics_regulation_order_record table for regulation orders extract (status, type, source, dates, validity)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS analytics_regulation_order_record (
                id UUID NOT NULL,
                uploaded_at TIMESTAMP(0) NOT NULL,
                record_uuid UUID NOT NULL,
                organization_uuid UUID NOT NULL,
                status VARCHAR(20) NOT NULL,
                category VARCHAR(50) NOT NULL,
                subject VARCHAR(50),
                source VARCHAR(32) NOT NULL,
                created_at TIMESTAMP(0) NOT NULL,
                publication_date TIMESTAMP(0),
                start_date TIMESTAMP(0),
                end_date TIMESTAMP(0),
                is_permanent BOOLEAN NOT NULL,
                validity_status VARCHAR(20) NOT NULL,
                PRIMARY KEY(id)
            );',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_ror_uploaded_at
            ON analytics_regulation_order_record (uploaded_at);',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_ror_record_uuid
            ON analytics_regulation_order_record (record_uuid);',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_ror_org_uuid
            ON analytics_regulation_order_record (organization_uuid);',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_ror_status
            ON analytics_regulation_order_record (status);',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_ror_source
            ON analytics_regulation_order_record (source);',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_ror_validity_status
            ON analytics_regulation_order_record (validity_status);',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS analytics_regulation_order_record');
    }
}

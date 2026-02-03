<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\MetabaseMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260129100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create analytics_organization_extract table for organization extract (type, nb users, nb published orders)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS analytics_organization_extract (
                id UUID NOT NULL,
                uploaded_at TIMESTAMP(0) NOT NULL,
                organization_uuid UUID NOT NULL,
                organization_name VARCHAR(255) NOT NULL,
                organization_type VARCHAR(32),
                nb_users INTEGER NOT NULL DEFAULT 0,
                nb_published_regulation_orders INTEGER NOT NULL DEFAULT 0,
                PRIMARY KEY(id)
            );',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_org_extract_uploaded_at
            ON analytics_organization_extract (uploaded_at);',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_org_extract_org_uuid
            ON analytics_organization_extract (organization_uuid);',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_org_extract_org_type
            ON analytics_organization_extract (organization_type);',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS analytics_organization_extract');
    }
}

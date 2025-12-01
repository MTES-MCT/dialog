<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\MetabaseMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251127100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create analytics_organization_coverage table for geographic coverage statistics';
    }

    public function up(Schema $schema): void
    {
        // Activer l'extension PostGIS si elle n'est pas déjà active
        $this->addSql('CREATE EXTENSION IF NOT EXISTS postgis;');

        // Créer la table pour stocker la couverture géographique des organisations
        $this->addSql(
            'CREATE TABLE IF NOT EXISTS analytics_organization_coverage (
                id UUID NOT NULL,
                uploaded_at TIMESTAMP(0),
                organization_uuid UUID NOT NULL,
                organization_name VARCHAR(255) NOT NULL,
                geometry geometry(GEOMETRY, 4326),
                PRIMARY KEY(id)
            );',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_org_coverage_uploaded_at
            ON analytics_organization_coverage (uploaded_at);',
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_org_coverage_org_uuid
            ON analytics_organization_coverage (organization_uuid);',
        );

        // Index spatial pour les requêtes géographiques
        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_analytics_org_coverage_geometry
            ON analytics_organization_coverage USING GIST (geometry);',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS analytics_organization_coverage');
    }
}

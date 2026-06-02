<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260527120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes to speed up MVT tile generation: GIST on location.geometry (bbox prefilter), btree on measure.type and regulation_order.category (filters).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_location_geometry ON location USING GIST (geometry) WHERE geometry IS NOT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_measure_type ON measure (type)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_regulation_order_category ON regulation_order (category)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_location_geometry');
        $this->addSql('DROP INDEX IF EXISTS idx_measure_type');
        $this->addSql('DROP INDEX IF EXISTS idx_regulation_order_category');
    }
}

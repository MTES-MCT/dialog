<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20240418122448 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location ADD road_geometry geometry(GEOMETRY, 4326) DEFAULT NULL');
        $this->addSql('COMMENT ON COLUMN location.road_geometry IS \'(DC2Type:geojson_geometry)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE location DROP road_geometry');
    }
}

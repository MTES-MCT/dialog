<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250123102525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add storage_area table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE storage_area (uuid UUID NOT NULL, location_uuid UUID DEFAULT NULL, description VARCHAR(255) NOT NULL, administrator VARCHAR(64) NOT NULL, road_number VARCHAR(16) NOT NULL, from_point_number VARCHAR(5) NOT NULL, from_side VARCHAR(1) NOT NULL, from_abscissa INT DEFAULT 0 NOT NULL, to_point_number VARCHAR(5) NOT NULL, to_side VARCHAR(1) NOT NULL, to_abscissa INT DEFAULT 0 NOT NULL, geometry geometry(GEOMETRY, 4326) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql('CREATE INDEX IDX_DEB9F67D517BE5E6 ON storage_area (location_uuid)');
        $this->addSql('COMMENT ON COLUMN storage_area.geometry IS \'(DC2Type:geojson_geometry)\'');
        $this->addSql('ALTER TABLE storage_area ADD CONSTRAINT FK_DEB9F67D517BE5E6 FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE storage_area DROP CONSTRAINT FK_DEB9F67D517BE5E6');
        $this->addSql('DROP TABLE storage_area');
    }
}

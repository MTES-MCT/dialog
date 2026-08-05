<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260730120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zone table: restrictions applied to an area drawn on the map (polygon).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE zone (uuid UUID NOT NULL, location_uuid UUID DEFAULT NULL, label VARCHAR(255) NOT NULL, geometry geometry(GEOMETRY, 4326) NOT NULL, PRIMARY KEY(uuid))');
        $this->addSql("COMMENT ON COLUMN zone.geometry IS '(DC2Type:geojson_geometry)'");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_A0EBC007517BE5E6 ON zone (location_uuid)');
        $this->addSql('ALTER TABLE zone ADD CONSTRAINT FK_ZONE_LOCATION FOREIGN KEY (location_uuid) REFERENCES location (uuid) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone DROP CONSTRAINT FK_ZONE_LOCATION');
        $this->addSql('DROP TABLE zone');
    }
}

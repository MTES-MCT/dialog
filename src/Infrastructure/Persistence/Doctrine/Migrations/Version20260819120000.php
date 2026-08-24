<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260819120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add raw_geojson.geometry: original drawn geometry, kept for re-edition now that exceptions can be subtracted from location.geometry.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE raw_geojson ADD geometry geometry(GEOMETRY, 4326) DEFAULT NULL');
        $this->addSql("COMMENT ON COLUMN raw_geojson.geometry IS '(DC2Type:geojson_geometry)'");
        // Les tracés existants n'ont pas d'exception : leur géométrie de localisation est le tracé d'origine.
        $this->addSql('UPDATE raw_geojson r SET geometry = l.geometry FROM location l WHERE l.uuid = r.location_uuid');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE raw_geojson DROP geometry');
    }
}

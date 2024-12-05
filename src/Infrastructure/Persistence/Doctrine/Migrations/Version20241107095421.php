<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241107095421 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE MATERIALIZED VIEW featureserver_restrictions AS
            SELECT
                loc.geometry::geometry(geometry, 4326) AS geometry,
                m.type AS measure_type,
                p.start_datetime,
                p.end_datetime,
                v.restricted_types,
                v.other_restricted_type_text,
                v.exempted_types,
                v.other_exempted_type_text,
                v.critair_types,
                v.heavyweight_max_weight,
                v.max_width,
                v.max_length,
                v.max_height,
                loc.road_type AS location_type,
                ns.city_code,
                ns.city_label,
                ns.road_name,
                nr.road_number,
                nr.administrator,
                nr.from_point_number,
                nr.from_side,
                nr.from_abscissa,
                nr.to_point_number,
                nr.to_side,
                nr.to_abscissa,
                rg.label AS geojson_label
            FROM period AS p
            INNER JOIN measure AS m ON m.uuid = p.measure_uuid
            INNER JOIN location AS loc ON loc.measure_uuid = m.uuid
            LEFT OUTER JOIN vehicle_set AS v ON v.measure_uuid = m.uuid
            LEFT OUTER JOIN named_street AS ns ON ns.location_uuid = loc.uuid
            LEFT OUTER JOIN numbered_road AS nr ON nr.location_uuid = nr.uuid
            LEFT OUTER JOIN raw_geojson AS rg ON rg.location_uuid = rg.uuid
            WITH DATA
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP MATERIALIZED VIEW featureserver_restrictions');
    }
}

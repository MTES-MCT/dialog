<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261125120000 extends AbstractMigration
{
    // Columns previously mapped with the Doctrine `array` type (PHP-serialized lists of strings), removed in DBAL 4.
    private const ARRAY_COLUMNS = [
        ['"user"', 'roles'],
        ['vehicle_set', 'restricted_types'],
        ['vehicle_set', 'critair_types'],
        ['vehicle_set', 'exempted_types'],
        ['dailyrange', 'applicable_days'],
    ];

    // DBAL 4 no longer uses "(DC2Type:...)" column comments.
    private const COMMENTED_COLUMNS = [
        ['storage_area', 'geometry', 'geojson_geometry'],
        ['organization', 'geometry', 'geojson_geometry'],
        ['whole_city_exception', 'geometry', 'geojson_geometry'],
        ['location', 'geometry', 'geojson_geometry'],
        ['zone', 'geometry', 'geojson_geometry'],
        ['raw_geojson', 'geometry', 'geojson_geometry'],
    ];

    public function getDescription(): string
    {
        return 'DBAL 4: convert PHP-serialized `array` columns to JSON and remove DC2Type column comments';
    }

    public function up(Schema $schema): void
    {
        foreach (self::ARRAY_COLUMNS as [$table, $column]) {
            // Serialized values are lists of strings without quotes, e.g. a:2:{i:0;s:8:"critair4";i:1;s:8:"critair5";}
            $this->addSql(\sprintf(
                'UPDATE %1$s SET %2$s = (
                    SELECT COALESCE(json_agg(m[1] ORDER BY ord), \'[]\'::json)::text
                    FROM regexp_matches(%2$s, \'s:\d+:"([^"]*)";\', \'g\') WITH ORDINALITY AS t(m, ord)
                ) WHERE %2$s IS NOT NULL',
                $table,
                $column,
            ));
            $this->addSql(\sprintf('ALTER TABLE %1$s ALTER %2$s TYPE JSON USING %2$s::json', $table, $column));
            $this->addSql(\sprintf('COMMENT ON COLUMN %s.%s IS NULL', $table, $column));
        }

        foreach (self::COMMENTED_COLUMNS as [$table, $column]) {
            $this->addSql(\sprintf('COMMENT ON COLUMN %s.%s IS NULL', $table, $column));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::COMMENTED_COLUMNS as [$table, $column, $type]) {
            $this->addSql(\sprintf('COMMENT ON COLUMN %s.%s IS \'(DC2Type:%s)\'', $table, $column, $type));
        }

        foreach (self::ARRAY_COLUMNS as [$table, $column]) {
            $this->addSql(\sprintf('ALTER TABLE %1$s ALTER %2$s TYPE TEXT USING %2$s::text', $table, $column));
            $this->addSql(\sprintf(
                'UPDATE %1$s SET %2$s = (
                    SELECT \'a:\' || count(*) || \':{\' || COALESCE(string_agg(format(\'i:%%s;s:%%s:"%%s";\', ord - 1, octet_length(v), v), \'\' ORDER BY ord), \'\') || \'}\'
                    FROM json_array_elements_text(%2$s::json) WITH ORDINALITY AS t(v, ord)
                ) WHERE %2$s IS NOT NULL',
                $table,
                $column,
            ));
            $this->addSql(\sprintf('COMMENT ON COLUMN %s.%s IS \'(DC2Type:array)\'', $table, $column));
        }
    }
}

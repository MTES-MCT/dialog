<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20241204152107 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE OR REPLACE FUNCTION public.f_ST_NormalizeGeometryCollection(geometry)
            RETURNS geometry
            LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
            AS $func$
                SELECT CASE
                    -- Les GeometryCollection à 1 seul élément ne sont pas conformes à la spec GeoJSON.
                    WHEN ST_GeometryType($1) = \'ST_GeometryCollection\' AND ST_NumGeometries($1) = 1
                        THEN ST_CollectionHomogenize($1)
                    ELSE $1
                END
            $func$;',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP FUNCTION IF EXISTS public.f_ST_NormalizeGeometryCollection');
    }
}

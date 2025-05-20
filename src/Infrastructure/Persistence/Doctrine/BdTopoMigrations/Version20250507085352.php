<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\BdTopoMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250507085352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return "Recrée la migration BD TOPO pour le passage à l'édition 2025";
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS postgis');
        $this->addSql('CREATE INDEX IF NOT EXISTS route_numerotee_ou_nommee_numero_gestionnaire_idx ON route_numerotee_ou_nommee (numero, gestionnaire);');
        $this->addSql('CREATE INDEX IF NOT EXISTS point_de_repere_route_numero_gestionnaire_cote_idx ON point_de_repere (route, numero, gestionnaire, cote);');
        $this->addSql('CREATE INDEX voie_nommee_identifiant_voie_ban_idx ON voie_nommee (identifiant_voie_ban)');
        $this->addSql('CREATE INDEX voie_nommee_insee_commune_idx ON voie_nommee (insee_commune)');
        $this->addSql('CREATE INDEX troncon_de_route_identifiant_voie_ban_gauche_idx ON troncon_de_route (identifiant_voie_ban_gauche)');
        $this->addSql('CREATE EXTENSION IF NOT EXISTS postgis_sfcgal');
        $this->addSql('CREATE INDEX IF NOT EXISTS point_de_repere_identifiant_de_section_idx ON point_de_repere (identifiant_de_section);');

        $this->addSql(<<<'SQL'
            CREATE OR REPLACE FUNCTION public.f_ST_NormalizeGeometryCollection(geometry)
            RETURNS geometry
            LANGUAGE sql IMMUTABLE PARALLEL SAFE STRICT
            AS $func$
                SELECT CASE
                    -- Les GeometryCollection à 1 seul élément ne sont pas conformes à la spec GeoJSON.
                    WHEN ST_GeometryType($1) = 'ST_GeometryCollection' AND ST_NumGeometries($1) = 1
                        -- ST_CollectionHomogenize('GEOMETRYCOLLECTION(POINT(0, 0))') renvoie 'POINT(0, 0)'
                        -- https://postgis.net/docs/ST_CollectionHomogenize.html
                        THEN ST_CollectionHomogenize($1)
                    ELSE $1
                END
            $func$;
            SQL,
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP FUNCTION IF EXISTS public.f_ST_NormalizeGeometryCollection');
        $this->addSql('DROP INDEX IF EXISTS point_de_repere_identifiant_de_section_idx');
        $this->addSql('DROP EXTENSION IF EXISTS postgis_sfcgal');
        $this->addSql('DROP INDEX IF EXISTS troncon_de_route_identifiant_voie_ban_gauche_idx');
        $this->addSql('DROP INDEX IF EXISTS voie_nommee_insee_commune_idx');
        $this->addSql('DROP INDEX IF EXISTS voie_nommee_identifiant_voie_ban_idx');
        $this->addSql('DROP INDEX IF EXISTS point_de_repere_route_numero_gestionnaire_cote_idx');
        $this->addSql('DROP INDEX IF EXISTS route_numerotee_ou_nommee_numero_gestionnaire_idx');
        $this->addSql('DROP EXTENSION IF EXISTS postgis');
    }
}

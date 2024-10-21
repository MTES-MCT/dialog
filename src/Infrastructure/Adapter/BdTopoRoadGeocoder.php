<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\IntersectionGeocoderInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class BdTopoRoadGeocoder implements RoadGeocoderInterface, IntersectionGeocoderInterface
{
    public function __construct(
        private Connection $bdtopoConnection,
    ) {
    }

    public function computeRoadLine(string $roadName, string $inseeCode): string
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                '
                    WITH voie_nommee as (
                        SELECT id_pseudo_fpb
                        FROM voie_nommee
                        WHERE f_bdtopo_voie_nommee_normalize_nom_minuscule(nom_minuscule) = f_bdtopo_voie_nommee_normalize_nom_minuscule(:nom_minuscule)
                        AND code_insee = :code_insee
                        LIMIT 1
                    )
                    SELECT ST_AsGeoJSON(ST_Force2D(ST_Collect(geometrie))) AS geometry
                    FROM troncon_de_route
                    INNER JOIN voie_nommee ON true
                    WHERE voie_nommee.id_pseudo_fpb = identifiant_voie_1_gauche
                ',
                [
                    'nom_minuscule' => $roadName,
                    'code_insee' => $inseeCode,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Road line query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if ($rows && $rows[0]['geometry']) {
            return $rows[0]['geometry'];
        }

        $message = \sprintf('no result found in voie_nommee for roadName="%s", inseeCode="%s"', $roadName, $inseeCode);
        throw new GeocodingFailureException($message);
    }

    public function findRoads(string $search, string $administrator): array
    {
        // Can search for a departmental road with the prefix "RD"
        if (str_starts_with(strtoupper($search), 'RD')) {
            $search = substr($search, 1);
        }

        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                '
                    SELECT numero
                    FROM route_numerotee_ou_nommee
                    WHERE numero LIKE :numero_pattern
                    AND gestionnaire = :gestionnaire
                    AND type_de_route = :type_de_route
                    LIMIT 10
                ',
                [
                    'numero_pattern' => \sprintf('%s%%', strtoupper($search)),
                    'gestionnaire' => $administrator,
                    'type_de_route' => 'Départementale',
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Departmental roads query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        $departmentalRoads = [];

        foreach ($rows as $row) {
            $departmentalRoads[] = [
                'roadNumber' => $row['numero'],
            ];
        }

        return $departmentalRoads;
    }

    public function computeRoad(string $roadNumber, string $administrator): string
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                '
                    SELECT ST_AsGeoJSON(ST_LineMerge(geometrie)) AS geometry
                    FROM route_numerotee_ou_nommee
                    WHERE numero = :numero
                    AND gestionnaire = :gestionnaire
                    AND type_de_route = :type_de_route
                    LIMIT 1
                ',
                [
                    'numero' => strtoupper($roadNumber),
                    'gestionnaire' => $administrator,
                    'type_de_route' => 'Départementale',
                ],
            );
        } catch (\Exception $exc) {
            throw new RoadGeocodingFailureException(\sprintf('Departmental roads query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if ($rows) {
            return $rows[0]['geometry'];
        }

        $message = \sprintf('no result found in route_numerotee_ou_nommee for roadNumber="%s", administrator="%s"', $roadNumber, $administrator);
        throw new RoadGeocodingFailureException($message);
    }

    public function computeReferencePoint(
        string $lineGeometry,
        string $administrator,
        string $roadNumber,
        string $pointNumber,
        string $side,
        int $abscissa,
    ): Coordinates {
        try {
            $row = $this->bdtopoConnection->fetchAssociative(
                // Principe : partir du PR indiqué, puis marcher **dans le sens
                // des PR croissants** d'une distance en mètres définie par `$abscissa`.
                'WITH route_prs AS (
                    SELECT *
                    FROM point_de_repere
                    WHERE route = :route
                    AND gestionnaire = :gestionnaire
                ),
                matching_pr as (
                    SELECT pr.abscisse as abscisse
                    FROM route_prs AS pr
                    WHERE pr.cote = :cote
                    AND pr.numero = :numero
                    LIMIT 1
                ),
                -- Attention : tous les calculs doivent être faits en mètres (unité des abscisses)
                -- Le système de coordonnées habituel EPSG:4326 est en angles / degrés
                -- On passe dans le système EPSG:2154 qui est en mètres
                first_pr_in_meters AS (
                    SELECT ST_Transform(pr.geometrie, 2154) AS geom
                    FROM route_prs AS pr
                    WHERE pr.ordre >= 0
                    ORDER BY pr.ordre ASC
                    LIMIT 1
                ),
                line_geom_in_meters AS (
                    SELECT ST_Transform(ST_LineMerge(ST_GeomFromGeoJSON(:geom)), 2154) AS geom
                ),
                line_order AS (
                    -- L\'ordre de numérisation (points de la géométrie) est le même que celui de numérotation (PR)
                    -- SI ET SEULEMENT SI le premier point de repère est dans la première moitié du linéaire ("côté début").
                    -- On se base sur la distance à vol d\'oiseau entre le premier PR et le premier point du linéaire pour éviter de devoir
                    -- calculer les abscisses curvilignes (car justement leur définition dépend du sens qu\'on essaie de déterminer ici).
                    -- Mais si la route est courbée, la distance à vol d\'oiseau est une moins bonne approximation de la distance curviligne.
                    -- Donc on s\'intéresse au premier quart de la route plutôt qu\'à la première moitié.
                    SELECT ST_Distance(ST_StartPoint(l.geom), fp.geom) < ST_Length(l.geom) / 4 AS is_aligned
                    FROM line_geom_in_meters AS l, first_pr_in_meters AS fp
                ),
                line_measure AS (
                    -- On associe une échelle ("measure" en postgis) au linéaire de route
                    -- allant de 0 à la longueur totale de la route,
                    -- dans le sens déterminé ci-dessus.
                    SELECT (
                        CASE WHEN o.is_aligned THEN ST_AddMeasure(
                            l.geom,
                            0,
                            ST_Length(l.geom)
                        )
                        ELSE ST_AddMeasure(
                            l.geom,
                            ST_Length(l.geom),
                            0
                        )
                        END
                    ) AS geom
                    FROM line_geom_in_meters AS l, line_order AS o
                ),
                -- ST_LocateAlong permet alors de trouver les coordonnées du point le long de cette échelle à partir du couple PR+abs indiqué.
                point_in_meters AS (
                    SELECT ST_LocateAlong(lm.geom, p.abscisse + :abscissa) AS geom
                    FROM matching_pr AS p, line_measure AS lm
                )
                -- On repasse en EPSG:4326
                SELECT ST_AsGeoJSON(ST_Transform(p.geom, 4326)) AS geom
                FROM point_in_meters AS p
                ',
                [
                    'geom' => $lineGeometry,
                    'route' => $roadNumber,
                    'gestionnaire' => $administrator,
                    'numero' => $pointNumber,
                    'abscissa' => $abscissa,
                    'cote' => $side,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Reference point query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if (!$row) {
            throw new GeocodingFailureException(\sprintf('no result found for roadNumber="%s", administrator="%s", pointNumber=%s', $roadNumber, $administrator, $pointNumber));
        }

        $lonLat = json_decode($row['geom'], associative: true);
        $coordinates = $lonLat['coordinates'];

        if (empty($coordinates)) {
            throw new AbscissaOutOfRangeException();
        }

        // Coordinates can be a POINT [1, 2] or a MULTIPOINT [[1, 2], [3, 4]]
        if (\is_array($coordinates[0])) {
            return Coordinates::fromLonLat($coordinates[0][0], $coordinates[0][1]);
        } else {
            return Coordinates::fromLonLat($coordinates[0], $coordinates[1]);
        }
    }

    public function findRoadNames(string $search, string $cityCode): array
    {
        // Build search query
        // https://www.postgresql.org/docs/current/datatype-textsearch.html#DATATYPE-TSQUERY
        $query = str_replace(' ', ' & ', trim($search)) . ':*';

        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                "
                    SELECT INITCAP(nom_minuscule) road_name
                    FROM voie_nommee
                    WHERE (
                        nom_minuscule_search @@ to_tsquery('french', :query::text)
                        OR :search % ANY(STRING_TO_ARRAY(f_bdtopo_voie_nommee_normalize_nom_minuscule(nom_minuscule), ' '))
                    )
                    AND code_insee = :cityCode
                    ORDER BY ts_rank(nom_minuscule_search, to_tsquery('french', :query::text)) DESC
                    LIMIT 7
                ",
                [
                    'cityCode' => $cityCode,
                    'query' => $query,
                    'search' => $search,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Road names query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        $roadNames = [];

        foreach ($rows as $row) {
            $roadNames[] = $row['road_name'];
        }

        return $roadNames;
    }

    public function findIntersectingRoadNames(string $search, string $roadName, string $cityCode): array
    {
        // Build search query
        // https://www.postgresql.org/docs/current/datatype-textsearch.html#DATATYPE-TSQUERY
        $query = $search ? str_replace(' ', ' & ', trim($search)) . ':*' : '';

        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                \sprintf(
                    'WITH ref AS (
                        SELECT ogc_fid, geometrie, nom_minuscule, code_insee
                        FROM voie_nommee
                        WHERE code_insee = :cityCode
                        AND f_bdtopo_voie_nommee_normalize_nom_minuscule(nom_minuscule) = f_bdtopo_voie_nommee_normalize_nom_minuscule(:roadName)
                        LIMIT 1
                    )
                    SELECT INITCAP(v.nom_minuscule) AS road_name
                    FROM voie_nommee AS v
                    INNER JOIN ref ON v.ogc_fid != ref.ogc_fid
                    WHERE v.code_insee = ref.code_insee
                    AND ST_Intersects(v.geometrie, ref.geometrie)
                    %s
                    ORDER BY road_name
                    ',
                    $query ? "AND nom_minuscule_search @@ to_tsquery('french', :query::text)" : '',
                ),
                [
                    'roadName' => $roadName,
                    'cityCode' => $cityCode,
                    'query' => $query,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Intersecting road names query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        $roadNames = [];

        foreach ($rows as $row) {
            $roadNames[] = $row['road_name'];
        }

        return $roadNames;
    }

    public function computeIntersection(string $roadName, string $otherRoadName, string $cityCode): Coordinates
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                \sprintf(
                    'SELECT
                        ST_X(ST_Centroid(ST_Intersection(v.geometrie, r.geometrie))) AS x,
                        ST_Y(ST_Centroid(ST_Intersection(v.geometrie, r.geometrie))) AS y
                    FROM voie_nommee AS v, voie_nommee AS r
                    WHERE v.code_insee = :cityCode
                    AND r.code_insee = :cityCode
                    AND f_bdtopo_voie_nommee_normalize_nom_minuscule(v.nom_minuscule) = f_bdtopo_voie_nommee_normalize_nom_minuscule(:roadName)
                    AND f_bdtopo_voie_nommee_normalize_nom_minuscule(r.nom_minuscule) = f_bdtopo_voie_nommee_normalize_nom_minuscule(:otherRoadName)
                    LIMIT 1
                    ',
                ),
                [
                    'cityCode' => $cityCode,
                    'roadName' => $roadName,
                    'otherRoadName' => $otherRoadName,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Intersecting road names query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if (!$rows) {
            $message = \sprintf('no intersection exists between roadName="%s" and otherRoadName="%s" in cityCode="%s"', $roadName, $otherRoadName, $cityCode);
            throw new GeocodingFailureException($message);
        }

        $x = $rows[0]['x'];
        $y = $rows[0]['y'];

        if (!$x || !$y) {
            $message = \sprintf(
                'no intersection found: one of roadName="%s" or otherRoadName="%s" does not exist in cityCode="%s"',
                $roadName,
                $otherRoadName,
                $cityCode,
            );
            throw new GeocodingFailureException($message);
        }

        return Coordinates::fromLonLat((float) $x, (float) $y);
    }

    public function findSectionsInArea(string $areaGeometry, array $excludeTypes = [], ?bool $clipToArea = false): string
    {
        $bdTopoExcludeTypes = [];

        foreach ($excludeTypes as $type) {
            $bdTopoExcludeTypes[] = match ($type) {
                $this::HIGHWAY => 'Type autoroutier',
                default => $type,
            };
        }

        try {
            $row = $this->bdtopoConnection->fetchAssociative(
                \sprintf(
                    'SELECT ST_AsGeoJSON(ST_Force2D(ST_Collect(%s))) AS geom
                    FROM troncon_de_route AS t
                    WHERE ST_Intersects(t.geometrie, :areaGeometry)
                    %s
                    ',
                    $clipToArea ? 'ST_Intersection(t.geometrie, :areaGeometry)' : 't.geometrie',
                    \count($bdTopoExcludeTypes) > 0 ? 'AND t.nature NOT IN (:types)' : '',
                ),
                [
                    'areaGeometry' => $areaGeometry,
                    'types' => $bdTopoExcludeTypes,
                ],
                [
                    'types' => ArrayParameterType::STRING,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Sections in area query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if (!$row['geom']) {
            // No sections in area, return empty collection instead of null
            return '{"type":"GeometryCollection","geometries":[]}';
        }

        return $row['geom'];
    }

    public function convertPolygonRoadToLines(string $geometry): string
    {
        try {
            $row = $this->bdtopoConnection->fetchAssociative(
                // ST_ApproximateMedialAxis permet de calculer la "ligne centrale" d'un polygone
                // https://postgis.net/docs/ST_ApproximateMedialAxis.html
                // Ici on l'utilise pour approximer le linéaire de voie à partir d'un polygone qui définit l'enveloppe de cette voie.
                'SELECT ST_AsGeoJSON(ST_ApproximateMedialAxis(ST_MakeValid(:geom))) AS geom',
                [
                    'geom' => $geometry,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Polygon road to lines query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if (!$row['geom']) {
            // No sections in area, return empty collection instead of null
            return '{"type":"GeometryCollection","geometries":[]}';
        }

        return $row['geom'];
    }
}

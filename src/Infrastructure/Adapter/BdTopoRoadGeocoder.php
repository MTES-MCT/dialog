<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use Doctrine\DBAL\Connection;

final class BdTopoRoadGeocoder implements RoadGeocoderInterface
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
                    SELECT ST_AsGeoJSON(geometrie) AS geometry
                    FROM voie_nommee
                    WHERE f_bdtopo_voie_nommee_normalize_nom_minuscule(nom_minuscule) = f_bdtopo_voie_nommee_normalize_nom_minuscule(:nom_minuscule)
                    AND code_insee = :code_insee
                    LIMIT 1
                ',
                [
                    'nom_minuscule' => $roadName,
                    'code_insee' => $inseeCode,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(sprintf('Road line query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if ($rows) {
            return $rows[0]['geometry'];
        }

        $message = sprintf('no result found in voie_nommee for roadName="%s", inseeCode="%s"', $roadName, $inseeCode);
        throw new GeocodingFailureException($message);
    }

    public function findRoads(string $search, string $administrator): array
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                '
                    SELECT numero
                    FROM route_numerotee_ou_nommee
                    WHERE numero LIKE :numero_pattern
                    AND gestionnaire = :gestionnaire
                    AND type_de_route = :type_de_route
                ',
                [
                    'numero_pattern' => sprintf('%s%%', strtoupper($search)),
                    'gestionnaire' => $administrator,
                    'type_de_route' => 'Départementale',
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(sprintf('Departmental roads query has failed: %s', $exc->getMessage()), previous: $exc);
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
                    SELECT ST_AsGeoJSON(geometrie) AS geometry
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
            throw new RoadGeocodingFailureException(sprintf('Departmental roads query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if ($rows) {
            return $rows[0]['geometry'];
        }

        $message = sprintf('no result found in route_numerotee_ou_nommee for roadNumber="%s", administrator="%s"', $roadNumber, $administrator);
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
                '
                    WITH pr as (
                        SELECT abscisse + :abscisse as abscisse
                        FROM point_de_repere
                        WHERE route = :route
                        AND gestionnaire = :gestionnaire
                        AND cote = :cote
                        AND numero = :numero
                        LIMIT 1
                    )
                    SELECT ST_AsGeoJSON(
                        ST_LocateAlong(
                            ST_AddMeasure(
                                ST_LineMerge(:geom),
                                0,
                                ST_Length(
                                    -- Convert to meters
                                    ST_Transform(
                                        ST_GeomFromGeoJSON(:geom),
                                        2154
                                    )
                                )
                            ),
                            pr.abscisse
                        )
                    ) as point
                    FROM pr
                ',
                [
                    'geom' => $lineGeometry,
                    'route' => $roadNumber,
                    'gestionnaire' => $administrator,
                    'numero' => $pointNumber,
                    'abscisse' => $abscissa,
                    'cote' => $side,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(sprintf('Reference point query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if (!$row) {
            throw new GeocodingFailureException(sprintf('no result found for roadNumber="%s", administrator="%s", pointNumber=%s', $roadNumber, $administrator, $pointNumber));
        }

        $lonLat = json_decode($row['point'], associative: true);
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
                    SELECT array_to_string(
                        -- BD TOPO contains lowercase road names. We capitalize non-stopwords.
                        -- Example: 'rue de france' -> 'Rue de France'. (Better than INITCAP('rue de france') -> 'Rue De France')
                        array(
                            SELECT CASE WHEN cardinality(t.lexemes) > 0 THEN INITCAP(t.token) ELSE t.token END
                            FROM ts_debug('french', nom_minuscule) AS t
                            WHERE t.alias NOT IN ('asciihword')
                        ),
                        ''
                    ) AS road_name
                    FROM voie_nommee
                    WHERE nom_minuscule_search @@ to_tsquery('french', :query::text)
                    AND code_insee = :cityCode
                    ORDER BY ts_rank(nom_minuscule_search, to_tsquery('french', :query::text)) DESC
                    LIMIT 7
                ",
                [
                    'cityCode' => $cityCode,
                    'query' => $query,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(sprintf('Road names query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        $roadNames = [];

        foreach ($rows as $row) {
            $roadNames[] = [
                'value' => $row['road_name'],
                'label' => $row['road_name'],
            ];
        }

        return $roadNames;
    }
}

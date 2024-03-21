<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\DepartmentalRoadGeocodingFailureException;
use App\Application\Exception\GeocodingFailureException;
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

    public function findDepartmentalRoads(string $search, string $administrator): array
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                '
                    SELECT numero, ST_AsGeoJSON(geometrie) AS geometry
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
                'geometry' => $row['geometry'],
            ];
        }

        return $departmentalRoads;
    }

    public function computeDepartmentalRoad(string $roadNumber, string $administrator): string
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
            throw new DepartmentalRoadGeocodingFailureException(sprintf('Departmental roads query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if ($rows) {
            return $rows[0]['geometry'];
        }

        $message = sprintf('no result found in route_numerotee_ou_nommee for roadNumber="%s", administrator="%s"', $roadNumber, $administrator);
        throw new DepartmentalRoadGeocodingFailureException($message);
    }

    public function computeReferencePoint(
        string $lineGeometry,
        string $administrator,
        string $roadNumber,
        string $pointNumber,
        string $side,
        ?int $abscissa,
    ): Coordinates {
        try {
            $rows = $this->bdtopoConnection->fetchAssociative(
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
                    'abscisse' => $abscissa ?? 0,
                    'cote' => $side,
                ],
            );

            $lonLat = json_decode($rows['point'], associative: true);
            $cordinates = current($lonLat['coordinates']);

            return Coordinates::fromLonLat($cordinates[0], $cordinates[1]);
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(sprintf('Reference point query has failed: %s', $exc->getMessage()), previous: $exc);
        }
    }
}

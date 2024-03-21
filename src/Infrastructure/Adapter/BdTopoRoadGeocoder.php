<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use App\Application\RoadGeocoderInterface;
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

    public function findReferencePointAbscisse(string $administrator, string $roadNumber, string $direction, int $pointNumber, int $abscissa): int
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                '
                    SELECT abscisse + :abscisse as abscisse
                    FROM point_de_repere
                    WHERE route = :route
                    AND gestionnaire = :gestionnaire
                    AND numero = :numero
                    AND cote = :cote
                ',
                [
                    'route' => $roadNumber,
                    'gestionnaire' => $administrator,
                    'cote' => $direction,
                    'numero' => $pointNumber,
                    'abscisse' => $abscissa,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(sprintf('Reference point query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if ($rows) {
            return $rows[0]['abscisse'];
        }

        throw new GeocodingFailureException(
            sprintf(
                'no result found in point_de_repere for gestionnaire="%s", route="%s", numero="%s", cote="%s"',
                $administrator,
                $roadNumber,
                $pointNumber,
                $direction,
            )
        );
    }
}

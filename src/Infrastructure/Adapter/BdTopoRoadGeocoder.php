<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\AbscissaOutOfRangeException;
use App\Application\Exception\GeocodingFailureException;
use App\Application\Exception\RoadGeocodingFailureException;
use App\Application\IntersectionGeocoderInterface;
use App\Application\RoadGeocoderInterface;
use App\Domain\Geography\Coordinates;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

final class BdTopoRoadGeocoder implements RoadGeocoderInterface, IntersectionGeocoderInterface
{
    public function __construct(
        private Connection $bdtopoConnection,
        private Connection $bdtopo2023Connection,
    ) {
    }

    public function computeRoadLine(string $roadBanId): string
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                '
                    SELECT ST_AsGeoJSON(ST_Force2D(f_ST_NormalizeGeometryCollection(ST_Collect(geometrie)))) AS geometry
                    FROM troncon_de_route
                    WHERE identifiant_voie_ban_gauche = :road_ban_id
                ',
                [
                    'road_ban_id' => $roadBanId,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Road line query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if ($rows && $rows[0]['geometry']) {
            return $rows[0]['geometry'];
        }

        $message = \sprintf('no result found for roadBanId="%s"', $roadBanId);
        throw new GeocodingFailureException($message);
    }

    public function computeRoadBanId(string $roadName, string $inseeCode): string
    {
        // Dans la BDTOPO à partir de janvier 2025, la table voie_nommee a été remodelée. La colonne 'nom_minuscule'
        // sur laquelle on se basait pour trouver une voie nommée a disparu. Dans la nouvelle table on doit utiliser la colonne
        // 'identifiant_voie_ban'. Les géométries n'ont pas changé, mais aucune clé d'interopérabilité n'a été conservée entre ces
        // deux tables qui permettrait de trouver l'identifiant_voie_ban d'une ancienne voie nommée. L'IGN nous a suggéré de faire
        // un "rapprochement géométrique" : on trouve la nouvelle voie nommée dont la géométrie est la plus proche de l'ancienne.

        // D'abord on récupère le linéaire de la voie tel que calculé auparavant, avec nom_minuscule.

        try {
            $row = $this->bdtopo2023Connection->fetchAssociative(
                <<<'SQL'
                    SELECT ST_AsGeoJSON(geometrie) AS geom
                    FROM voie_nommee
                    WHERE f_bdtopo_voie_nommee_normalize_nom_minuscule(nom_minuscule) = f_bdtopo_voie_nommee_normalize_nom_minuscule(:nom_minuscule)
                    AND code_insee = :code_insee
                    LIMIT 1
                SQL,
                [
                    'nom_minuscule' => $roadName,
                    'code_insee' => $inseeCode,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Road line 2023 query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if (empty($row['geom'])) {
            $message = \sprintf('no result found in voie_nommee 2023 for roadName="%s", inseeCode="%s"', $roadName, $inseeCode);
            throw new GeocodingFailureException($message);
        }

        $roadLine2023 = $row['geom'];

        // On trouve ensuite l'identifiant voie BAN de la voie nommée dont la géométrie est la plus proche.

        try {
            $row = $this->bdtopoConnection->fetchAssociative(
                <<<'SQL'
                    SELECT v.identifiant_voie_ban AS road_ban_id
                    FROM voie_nommee AS v
                    WHERE v.insee_commune = :city_code
                    -- ST_HausdorffDistance() donne un indice de la "similarité" entre deux géométries.
                    -- C'est la plus grande distance qui sépare deux points de deux géométries A et B.
                    -- Donc A et B sont similaires si leur distance de Hausdorff est petite.
                    -- https://postgis.net/docs/ST_HausdorffDistance.html
                    -- (On ne pouvait pas utiliser ST_Distance() car elle renvoie au contraire la plus *petite* des
                    -- distances entre deux points de A et B. Or deux géométries peuvent avoir quelques points très proches
                    -- mais être très différentes pour le reste...)
                    ORDER BY ST_HausdorffDistance(v.geometrie, ST_SetSRID(ST_GeomFromGeoJSON(:road_line_2023), 4326)) ASC
                    LIMIT 1
                SQL,
                [
                    'road_line_2023' => $roadLine2023,
                    'city_code' => $inseeCode,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Road line query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if (empty($row['road_ban_id'])) {
            $message = \sprintf('No road_ban_id found for roadName="%s" and inseeCode="%s"', $roadName, $inseeCode);
            throw new GeocodingFailureException($message);
        }

        return $row['road_ban_id'];
    }

    public function findRoads(string $search, string $roadType, string $administrator): array
    {
        // Can search for a departmental road with the prefix "RD"
        if (str_starts_with(strtoupper($search), 'RD')) {
            $search = substr($search, 1);
        }

        // Can search for a national road with the prefix "RN"
        if (str_starts_with(strtoupper($search), 'RN')) {
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
                    ORDER BY numero
                    LIMIT 10
                ',
                [
                    'numero_pattern' => \sprintf('%s%%', strtoupper($search)),
                    'gestionnaire' => $administrator,
                    'type_de_route' => $roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value ? 'Départementale' : 'Nationale',
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Numbered road query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        $results = [];

        foreach ($rows as $row) {
            $results[] = [
                'roadNumber' => $row['numero'],
            ];
        }

        return $results;
    }

    public function computeRoad(string $roadType, string $administrator, string $roadNumber): string
    {
        $numero = strtoupper($roadNumber);
        $typeDeRoute = $roadType === RoadTypeEnum::DEPARTMENTAL_ROAD->value ? 'Départementale' : 'Nationale';

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
                    'numero' => $numero,
                    'gestionnaire' => $administrator,
                    'type_de_route' => $typeDeRoute,
                ],
            );
        } catch (\Exception $exc) {
            throw new RoadGeocodingFailureException($roadType, \sprintf('Numbered road query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if ($rows) {
            return $rows[0]['geometry'];
        }

        $message = \sprintf(
            'no result found in route_numerotee_ou_nommee for numero="%s", gestionnaire="%s", type_de_route="%s"',
            $numero,
            $administrator,
            $typeDeRoute,
        );
        throw new RoadGeocodingFailureException($roadType, $message);
    }

    public function findReferencePoints(string $search, string $administrator, string $roadNumber): array
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                'SELECT
                    DISTINCT p.numero AS point_number,
                    p.numero::integer AS _point_number_int, -- Must be selected because appears in ORDER BY
                    p.code_insee_du_departement as department_code,
                    (
                        SELECT COUNT(DISTINCT(pp.code_insee_du_departement))
                        FROM point_de_repere AS pp
                        WHERE pp.numero = p.numero
                        AND pp.gestionnaire = p.gestionnaire
                        AND pp.route = p.route
                        AND pp.type_de_pr LIKE \'PR%\'
                    ) AS num_departments
                FROM point_de_repere AS p
                WHERE p.numero LIKE :numero_pattern
                AND p.gestionnaire = :gestionnaire
                AND p.route = :route
                AND p.type_de_pr LIKE \'PR%\'
                ORDER BY p.numero::integer, p.code_insee_du_departement
                ',
                [
                    'numero_pattern' => \sprintf('%s%%', $search),
                    'gestionnaire' => $administrator,
                    'route' => $roadNumber,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Reference points query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        $results = [];

        foreach ($rows as $row) {
            $results[] = [
                'pointNumber' => $row['point_number'],
                'departmentCode' => $row['department_code'],
                'numDepartments' => $row['num_departments'],
            ];
        }

        return $results;
    }

    public function computeReferencePoint(
        string $roadType,
        string $administrator,
        string $roadNumber,
        ?string $departmentCode,
        string $pointNumber,
        string $side,
        int $abscissa,
    ): Coordinates {
        try {
            // Pour trouver un PR+abs, on trouve le PR, puis on remonte sa section de point de repère d'une distance indiquée par :abscissa.
            $row = $this->bdtopoConnection->fetchAssociative(
                \sprintf(
                    'SELECT ST_AsGeoJSON(
                        ST_GeometryN(
                            ST_LocateAlong(
                                ST_AddMeasure(s.geometrie, 0, ST_Length(s.geometrie::geography)),
                                ST_InterpolatePoint(
                                    ST_AddMeasure(s.geometrie, 0, ST_Length(s.geometrie::geography)),
                                    p.geometrie
                                ) + :abscissa * (
                                    -- L ordre de numérisation (= ordre des points dans la géométrie de la section)
                                    -- n\'est pas forcément l\'ordre des points de repère (= ordre de numérotation).
                                    -- On détecte si les deux ordres correspondent avec cette règle :
                                    -- => Les ordres sont alignés si et seulement si le 1er PR de la section est situé dans le 1er quart de la section
                                    -- Si les ordres sont inversés, il faut compter les abscisses dans l\'autre sens
                                    CASE WHEN ST_Distance(
                                        ST_StartPoint(s.geometrie),
                                        (
                                            SELECT pp.geometrie
                                            FROM point_de_repere AS pp
                                            WHERE pp.identifiant_de_section = s.identifiant_de_section
                                            AND pp.ordre >= 0
                                            ORDER BY pp.ordre ASC
                                            LIMIT 1
                                        )
                                    ) < ST_Length(s.geometrie) / 4
                                    THEN 1
                                    ELSE -1
                                    END
                                )
                            ),
                            1
                        )
                    ) AS geom
                    FROM point_de_repere AS p
                    LEFT JOIN section_de_points_de_repere AS s
                        ON p.identifiant_de_section = s.identifiant_de_section
                    WHERE p.gestionnaire = :administrator
                        AND p.route = :roadNumber
                        AND p.numero = :pointNumber
                        AND p.cote = :side
                        -- Types dans la BDTOPO : C, CS, DS, FS, PR, PR0, PRF.
                        -- On ne garde que les types PR, PR0 et PRF, car les autres types ne correspondent pas à des PR "physiques".
                        AND p.type_de_pr LIKE \'PR%%\'
                        %s
                    ',
                    empty($departmentCode) ? '' : 'AND p.code_insee_du_departement = :departmentCode',
                ),
                [
                    'administrator' => $administrator,
                    'roadNumber' => $roadNumber,
                    'pointNumber' => $pointNumber,
                    'departmentCode' => $departmentCode,
                    'side' => $side,
                    'abscissa' => $abscissa,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Reference point query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if (!$row) {
            throw new GeocodingFailureException(\sprintf('no result found for roadNumber="%s", administrator="%s", departmentCode="%s", pointNumber=%s', $roadNumber, $administrator, $departmentCode, $pointNumber));
        }

        if (empty($row['geom'])) {
            throw new AbscissaOutOfRangeException($roadType);
        }

        $lonLat = json_decode($row['geom'], associative: true);
        $coordinates = $lonLat['coordinates'];

        // Coordinates can be a POINT [1, 2] or a MULTIPOINT [[1, 2], [3, 4]]
        if (\is_array($coordinates[0])) {
            return Coordinates::fromLonLat($coordinates[0][0], $coordinates[0][1]);
        } else {
            return Coordinates::fromLonLat($coordinates[0], $coordinates[1]);
        }
    }

    public function findSides(string $administrator, string $roadNumber, ?string $departmentCode, string $pointNumber): array
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                \sprintf(
                    'SELECT DISTINCT p.cote AS side
                    FROM point_de_repere AS p
                    WHERE p.gestionnaire = :gestionnaire
                    AND p.route = :route
                    AND p.numero = :numero
                    AND p.type_de_pr LIKE \'PR%%\'
                    %s
                    ',
                    empty($departmentCode) ? '' : 'AND p.code_insee_du_departement = :departmentCode',
                ),
                [
                    'route' => $roadNumber,
                    'gestionnaire' => $administrator,
                    'numero' => $pointNumber,
                    'departmentCode' => $departmentCode,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Sides query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        $sides = [];

        foreach ($rows as $row) {
            $sides[] = $row['side'];
        }

        return $sides;
    }

    public function findIntersectingNamedStreets(string $roadBanId, string $cityCode): array
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                <<<'SQL'
                    SELECT v.identifiant_voie_ban AS road_ban_id, v.nom_voie_ban AS road_name
                    FROM voie_nommee AS v
                    WHERE ST_Intersects(v.geometrie, (SELECT v2.geometrie FROM voie_nommee AS v2 WHERE v2.identifiant_voie_ban = :road_ban_id LIMIT 1))
                    AND LENGTH(v.identifiant_voie_ban) > 0
                    AND v.identifiant_voie_ban <> :road_ban_id
                    AND v.insee_commune = :city_code
                    ORDER BY road_name
                SQL,
                [
                    'road_ban_id' => $roadBanId,
                    'city_code' => $cityCode,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Intersecting road names query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        $namedStreets = [];

        foreach ($rows as $row) {
            $namedStreets[] = [
                'roadBanId' => $row['road_ban_id'],
                'roadName' => $row['road_name'],
            ];
        }

        return $namedStreets;
    }

    public function computeIntersection(string $roadBanId, string $otherRoadBanId): Coordinates
    {
        try {
            $rows = $this->bdtopoConnection->fetchAllAssociative(
                \sprintf(
                    'SELECT
                        ST_X(ST_Centroid(ST_Intersection(v1.geometrie, v2.geometrie))) AS x,
                        ST_Y(ST_Centroid(ST_Intersection(v1.geometrie, v2.geometrie))) AS y
                    FROM voie_nommee AS v1, voie_nommee AS v2
                    WHERE v1.identifiant_voie_ban = :roadBanId
                    AND v2.identifiant_voie_ban = :otherRoadBanId
                    LIMIT 1
                    ',
                ),
                [
                    'roadBanId' => $roadBanId,
                    'otherRoadBanId' => $otherRoadBanId,
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Intersecting road names query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if (!$rows) {
            $message = \sprintf('no intersection exists between roadBanId="%s" and otherRoadBanId="%s"', $roadBanId, $otherRoadBanId);
            throw new GeocodingFailureException($message);
        }

        $x = $rows[0]['x'];
        $y = $rows[0]['y'];

        if (!$x || !$y) {
            $message = \sprintf(
                'no intersection found: one of roadBanId="%s" or otherRoadBanId="%s" does not exist',
                $roadBanId,
                $otherRoadBanId,
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
                    'SELECT ST_AsGeoJSON(ST_Force2D(f_ST_NormalizeGeometryCollection(ST_Collect(%s)))) AS geom
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
                // Si la géométrie n'est pas un polygône, on la renvoie telle quelle.
                'SELECT ST_AsGeoJSON(
                    CASE
                    WHEN ST_GeometryType((:geom)::geometry) IN (\'ST_Polygon\', \'ST_MultiPolygon\')
                    THEN ST_ApproximateMedialAxis(ST_MakeValid(:geom))
                    ELSE (:geom)::geometry
                    END
                ) AS geom',
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

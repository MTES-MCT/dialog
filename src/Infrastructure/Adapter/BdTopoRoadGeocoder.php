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
        private Connection $bdtopo2025Connection,
    ) {
    }

    public function computeRoadLine(string $roadBanId): string
    {
        try {
            $rows = $this->bdtopo2025Connection->fetchAllAssociative(
                '
                    SELECT ST_AsGeoJSON(ST_Force2D(f_ST_NormalizeGeometryCollection(ST_Collect(geometrie)))) AS geometry
                    FROM troncon_de_route
                    WHERE identifiant_voie_ban_gauche IN (:road_ban_id_lower, :road_ban_id_upper)
                ',
                [
                    'road_ban_id_lower' => strtolower($roadBanId),
                    'road_ban_id_upper' => strtoupper($roadBanId),
                ],
            );
        } catch (\Exception $exc) {
            throw new GeocodingFailureException(\sprintf('Road line query has failed: %s', $exc->getMessage()), previous: $exc);
        }

        if ($rows && $rows[0]['geometry']) {
            return $rows[0]['geometry'];
        }

        $message = \sprintf("no result found for roadBanId='%s'", $roadBanId);
        throw new GeocodingFailureException($message);
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
            $rows = $this->bdtopo2025Connection->fetchAllAssociative(
                '
                    SELECT DISTINCT numero
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
            $rows = $this->bdtopo2025Connection->fetchAllAssociative(
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
            $rows = $this->bdtopo2025Connection->fetchAllAssociative(
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
            $row = $this->bdtopo2025Connection->fetchAssociative(
                \sprintf(
                    'WITH pr_section AS (
                        -- Trouver la section qui contient le PR de référence
                        SELECT
                            s.identifiant_de_section,
                            s.geometrie,
                            ST_Length(s.geometrie::geography) AS section_length,
                            ST_LineLocatePoint(s.geometrie, p.geometrie) AS pr_position,
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
                            END AS direction
                        FROM point_de_repere AS p
                        LEFT JOIN section_de_points_de_repere AS s
                            ON p.identifiant_de_section = s.identifiant_de_section
                        WHERE p.gestionnaire = :administrator
                        AND p.route = :roadNumber
                        AND p.numero = :pointNumber
                        AND p.cote = :side
                        AND p.type_de_pr LIKE \'PR%%\'
                        %s
                    ),
                    next_pr AS (
                        -- Trouver le PR suivant sur la même route
                        SELECT
                            p2.numero,
                            p2.geometrie,
                            s2.identifiant_de_section,
                            s2.geometrie AS section_geometrie,
                            ST_Length(s2.geometrie::geography) AS section_length,
                            ST_LineLocatePoint(s2.geometrie, p2.geometrie) AS pr_position
                        FROM pr_section AS ps
                        LEFT JOIN point_de_repere AS p2 ON p2.gestionnaire = :administrator
                            AND p2.route = :roadNumber
                            AND p2.cote = :side
                            AND p2.numero = CAST(:pointNumber + 1 AS VARCHAR)
                            AND p2.type_de_pr LIKE \'PR%%\'
                        LEFT JOIN section_de_points_de_repere AS s2
                            ON p2.identifiant_de_section = s2.identifiant_de_section
                    ),
                    position_calculation AS (
                        -- Calculer la position cible
                        SELECT
                            ps.identifiant_de_section,
                            ps.geometrie,
                            ps.section_length,
                            ps.pr_position,
                            ps.direction,
                            (:abscissa * ps.direction / ps.section_length) AS abscissa_offset,
                            ps.pr_position + (:abscissa * ps.direction / ps.section_length) AS raw_position,
                            CASE
                                WHEN ps.pr_position + (:abscissa * ps.direction / ps.section_length) <= 1.0
                                     AND ps.pr_position + (:abscissa * ps.direction / ps.section_length) >= 0.0 THEN
                                    -- Position dans la section courante
                                    ps.pr_position + (:abscissa * ps.direction / ps.section_length)
                                WHEN ps.pr_position + (:abscissa * ps.direction / ps.section_length) > 1.0
                                     AND np.identifiant_de_section IS NOT NULL THEN
                                    -- Débordement vers la section suivante : calculer la position dans la section du PR suivant
                                    LEAST(
                                        np.pr_position + ((:abscissa * ps.direction - (1.0 - ps.pr_position) * ps.section_length) / np.section_length),
                                        1.0
                                    )
                                WHEN ps.pr_position + (:abscissa * ps.direction / ps.section_length) > 1.0 THEN
                                    -- Pas de section suivante : s\'arrêter à la fin de la section courante
                                    1.0
                                ELSE
                                    -- Position négative : s\'arrêter au début de la section courante
                                    0.0
                            END AS final_position,
                            -- Déterminer quelle section utiliser
                            CASE
                                WHEN ps.pr_position + (:abscissa * ps.direction / ps.section_length) > 1.0
                                     AND np.identifiant_de_section IS NOT NULL THEN
                                    -- Utiliser la section suivante
                                    np.identifiant_de_section
                                ELSE
                                    -- Utiliser la section courante
                                    ps.identifiant_de_section
                            END AS target_section_id,
                            COALESCE(np.section_geometrie, ps.geometrie) AS target_geometrie,
                            COALESCE(np.section_length, ps.section_length) AS target_section_length
                        FROM pr_section AS ps
                        LEFT JOIN next_pr AS np ON ps.pr_position + (:abscissa * ps.direction / ps.section_length) > 1.0
                    )
                    SELECT ST_AsGeoJSON(
                        ST_LineInterpolatePoint(
                            pc.target_geometrie,
                            pc.final_position
                        )
                    ) AS geom,
                    pc.pr_position,
                    pc.direction,
                    pc.abscissa_offset,
                    pc.raw_position,
                    pc.final_position,
                    pc.target_section_id,
                    pc.target_geometrie IS NOT NULL AS has_next_section
                    FROM position_calculation AS pc
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
            $rows = $this->bdtopo2025Connection->fetchAllAssociative(
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
            $rows = $this->bdtopo2025Connection->fetchAllAssociative(
                <<<'SQL'
                    SELECT v.identifiant_voie_ban AS road_ban_id, v.nom_voie_ban AS road_name
                    FROM voie_nommee AS v
                    WHERE ST_Intersects(v.geometrie, (SELECT v2.geometrie FROM voie_nommee AS v2 WHERE v2.identifiant_voie_ban = :road_ban_id LIMIT 1))
                    AND LENGTH(v.identifiant_voie_ban) > 0
                    AND v.identifiant_voie_ban <> :road_ban_id
                    AND v.insee_commune = :city_code
                    GROUP BY v.identifiant_voie_ban, v.nom_voie_ban
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
            $rows = $this->bdtopo2025Connection->fetchAllAssociative(
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
            $row = $this->bdtopo2025Connection->fetchAssociative(
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
            $row = $this->bdtopo2025Connection->fetchAssociative(
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

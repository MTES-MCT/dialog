<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Geography\Coordinates;

interface RoadGeocoderInterface
{
    public const HIGHWAY = 'HIGHWAY';

    public function computeRoadLine(string $roadBanId): string;

    /**
     * Calcule la géométrie de toutes les voies nommées d'une commune (code INSEE).
     *
     * @param string[] $excludedRoadBanIds Voies entières exclues exactement par leur identifiant BAN (NOT IN)
     * @param string[] $subtractGeometries Géométries GeoJSON soustraites géométriquement (ST_Difference avec un petit buffer)
     */
    public function computeCityGeometry(string $cityCode, array $excludedRoadBanIds = [], array $subtractGeometries = []): string;

    /**
     * Soustrait géométriquement des géométries GeoJSON d'une géométrie de base
     * (ST_Difference avec un petit buffer) : utilisé pour retirer les exceptions
     * d'un tracé de zone ou d'un tracé libre.
     *
     * @param string[] $subtractGeometries
     */
    public function subtractGeometries(string $geometry, array $subtractGeometries): string;

    public function getRoadBanIdFromName(string $roadName, string $inseeCode): string;

    public function findRoads(string $search, string $roadType, string $administrator): array;

    public function computeRoad(string $roadType, string $administrator, string $roadNumber): string;

    public function findReferencePoints(string $search, string $administrator, string $roadNumber): array;

    /**
     * Calcule la géométrie (FeatureCollection GeoJSON) de tous les points de repère (PR)
     * d'une route numérotée, pour affichage informatif sur une carte.
     *
     * Chaque Feature est un point avec les propriétés `pointNumber`, `departmentCode` et `label`.
     */
    public function computeReferencePoints(string $administrator, string $roadNumber): string;

    public function computeReferencePoint(
        string $roadType,
        string $administrator,
        string $roadNumber,
        ?string $departmentCode,
        string $pointNumber,
        string $side,
        int $abscissa,
    ): Coordinates;

    public function findSides(string $administrator, string $roadNumber, ?string $departmentCode, string $pointNumber): array;

    public function findSectionsInArea(string $areaGeometry, array $excludeTypes = [], ?bool $clipToArea = false): string;

    public function findNearbyStreets(string $geometry, int $radiusMeters = 100, int $limit = 10): array;
}

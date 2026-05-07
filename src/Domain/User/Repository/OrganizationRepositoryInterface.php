<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

use App\Application\Organization\View\MapBboxView;
use App\Application\User\View\OrganizationView;
use App\Domain\User\Organization;

interface OrganizationRepositoryInterface
{
    /** @return OrganizationView[] */
    public function findAll(): array;

    public function findAllEntities(): array;

    public function findOneByUuid(string $uuid): ?Organization;

    public function findOneBySiret(string $siret): ?Organization;

    public function countOrganizations(): int;

    public function add(Organization $organization): void;

    public function flush(): void;

    public function canInterveneOnGeometry(string $uuid, string $geometry): bool;

    public function findAllForStatistics(): array;

    public function findAllForMetabaseExport(): array;

    /**
     * Returns the bounding box (in EPSG:4326) used for the initial map view:
     * - if $userUuid is given, the bbox of the user's first organization (with a non-empty geometry);
     * - otherwise, the bbox of a randomly picked organization among the cached top published organizations.
     */
    public function findInitialMapBbox(?string $userUuid): ?MapBboxView;

    /**
     * Refreshes the `top_published_organization` cache table with the top
     * organizations by number of published regulation orders, alongside their bbox.
     */
    public function refreshTopPublishedOrganizations(int $limit = 10): void;

    /**
     * Calcule le centroïde d'une géométrie GeoJSON avec PostGIS.
     * Utilise ST_PointOnSurface pour garantir que le point est sur la surface de la géométrie.
     *
     * @return string GeoJSON Point
     */
    public function computeCentroidFromGeoJson(string $geoJson): string;
}

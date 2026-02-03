<?php

declare(strict_types=1);

namespace App\Domain\User\Repository;

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
     * Calcule le centroïde d'une géométrie GeoJSON avec PostGIS.
     * Utilise ST_PointOnSurface pour garantir que le point est sur la surface de la géométrie.
     *
     * @return string GeoJSON Point
     */
    public function computeCentroidFromGeoJson(string $geoJson): string;
}

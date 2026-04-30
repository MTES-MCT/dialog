<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\Location\Location;

interface LocationRepositoryInterface
{
    public function add(Location $location): Location;

    public function delete(Location $location): void;

    public function findOneByUuid(string $uuid): ?Location;

    public function findAllForMapAsGeoJSON(
        bool $includePermanentRegulations = false,
        bool $includeTemporaryRegulations = false,
        array $measureTypes = [],
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
    ): string;

    public function findGeometriesForRegulationOrderRecord(string $uuid): array;
    
    /**
     * Returns a Mapbox Vector Tile (MVT) binary blob with the locations of the
     * published regulation orders matching the given filters, restricted to the
     * geographical extent of the (z, x, y) tile.
     */
    public function findRestrictionsAsMVT(
        int $z,
        int $x,
        int $y,
        bool $includePermanentRegulations = false,
        bool $includeTemporaryRegulations = false,
        array $measureTypes = [],
        ?\DateTimeInterface $startDate = null,
        ?\DateTimeInterface $endDate = null,
    ): string;

    /** @return Location[] */
    public function findAllWithoutGeometry(): array;
}

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

    /**
     * Same as {@see findRestrictionsAsMVT()} but restricted to a single regulation
     * order record (any status). Used by the Word export to render the arrêté's
     * own map as MVT tiles, independently of the public map filters.
     */
    public function findRestrictionsAsMVTForRegulationOrderRecord(
        string $regulationOrderRecordUuid,
        int $z,
        int $x,
        int $y,
    ): string;

    /** @return Location[] */
    public function findAllWithoutGeometry(): array;
}

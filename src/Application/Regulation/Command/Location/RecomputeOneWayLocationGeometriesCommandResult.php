<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

final readonly class RecomputeOneWayLocationGeometriesCommandResult
{
    public function __construct(
        public int $numLocations,
        /** @var string[] UUID des emprises dont la géométrie a changé (ou changerait en mode « dry run ») */
        public array $updatedLocationUuids,
        /** @var string[] UUID des emprises dont la géométrie recalculée est identique à celle enregistrée */
        public array $unchangedLocationUuids,
        /** @var array<string, \Throwable> Exceptions de géocodage, indexées par UUID d'emprise */
        public array $exceptions,
    ) {
    }
}

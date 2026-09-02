<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Query\Location\GetRawGeoJSONGeometryQuery;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\RawGeoJSON;

final class SaveRawGeoJSONCommand implements RoadCommandInterface
{
    public ?string $roadType = null; // Used by validation
    public ?string $label = null;
    // Tracé dessiné (GeoJSON). La géométrie de la localisation est calculée à partir de ce
    // tracé en soustrayant les éventuelles exceptions à l'enregistrement.
    public ?string $geometry = null;
    /** @var SaveWholeCityExceptionCommand[] */
    public array $exceptions = [];
    public ?Location $location = null;

    public function __construct(
        public readonly ?RawGeoJSON $rawGeoJSON = null,
    ) {
        $this->roadType = $rawGeoJSON?->getLocation()?->getRoadType();
        $this->label = $rawGeoJSON?->getLabel();
        // Repli sur la géométrie de la localisation pour les tracés créés avant
        // l'introduction des exceptions (pas de tracé d'origine stocké).
        $this->geometry = $rawGeoJSON?->getGeometry() ?? $rawGeoJSON?->getLocation()->getGeometry();

        foreach ($rawGeoJSON?->getLocation()->getExceptions() ?? [] as $exception) {
            $this->exceptions[] = new SaveWholeCityExceptionCommand($exception);
        }
    }

    // Road command interface

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getGeometryQuery(): QueryInterface
    {
        return new GetRawGeoJSONGeometryQuery($this, $this->location);
    }

    public function clean(): void
    {
        // On retire les exceptions incomplètes (ex. lignes ajoutées puis laissées vides dans le formulaire).
        $this->exceptions = array_values(array_filter(
            $this->exceptions,
            fn (SaveWholeCityExceptionCommand $exception) => $exception->isComplete(),
        ));

        foreach ($this->exceptions as $exception) {
            $exception->clean();
        }
    }
}

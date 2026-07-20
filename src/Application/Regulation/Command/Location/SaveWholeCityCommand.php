<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Query\Location\GetWholeCityGeometryQuery;
use App\Domain\Regulation\Location\Location;

final class SaveWholeCityCommand implements RoadCommandInterface
{
    public ?string $roadType = null; // Used by validation
    public ?string $cityCode = null;
    public ?string $cityLabel = null;
    /** @var SaveWholeCityExceptionCommand[] */
    public array $exceptions = [];
    public ?string $geometry = null;
    public ?Location $location = null;

    public function __construct(
        // The "ville entière" road type has no dedicated sub-entity: its data lives on the location.
        ?Location $location = null,
    ) {
        if ($location) {
            $this->location = $location;
            $this->roadType = $location->getRoadType();
            $this->cityCode = $location->getCityCode();
            $this->cityLabel = $location->getCityLabel();

            foreach ($location->getExceptions() as $exception) {
                $this->exceptions[] = new SaveWholeCityExceptionCommand($exception);
            }
        }
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

    /**
     * Exceptions « voie entière », exclues exactement par leur identifiant BAN (les autres
     * sont soustraites géométriquement — voir GetWholeCityGeometryQueryHandler).
     *
     * @return string[]
     */
    public function getExcludedRoadBanIds(): array
    {
        $ids = [];

        foreach ($this->exceptions as $exception) {
            if ($roadBanId = $exception->getExcludedRoadBanId()) {
                $ids[] = $roadBanId;
            }
        }

        return $ids;
    }

    // Road command interface

    public function setLocation(Location $location): void
    {
        $this->location = $location;
    }

    public function getGeometryQuery(): QueryInterface
    {
        return new GetWholeCityGeometryQuery($this, $this->location, $this->geometry);
    }
}

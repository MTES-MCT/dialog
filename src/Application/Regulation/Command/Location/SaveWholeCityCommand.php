<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\QueryInterface;
use App\Application\Regulation\Query\Location\GetWholeCityGeometryQuery;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\WholeCity;

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
        public readonly ?WholeCity $wholeCity = null,
    ) {
        $this->roadType = $wholeCity?->getLocation()?->getRoadType();
        $this->cityCode = $wholeCity?->getCityCode();
        $this->cityLabel = $wholeCity?->getCityLabel();

        if ($wholeCity) {
            foreach ($wholeCity->getExceptions() as $exception) {
                $this->exceptions[] = new SaveWholeCityExceptionCommand($exception);
            }
        }
    }

    public function clean(): void
    {
        // Drop incomplete exceptions (e.g. empty rows added then left blank in the form)
        $this->exceptions = array_values(array_filter(
            $this->exceptions,
            fn (SaveWholeCityExceptionCommand $exception) => !empty($exception->roadBanId),
        ));
    }

    /**
     * @return string[]
     */
    public function getExcludedRoadBanIds(): array
    {
        $ids = [];

        foreach ($this->exceptions as $exception) {
            if (!empty($exception->roadBanId)) {
                $ids[] = $exception->roadBanId;
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

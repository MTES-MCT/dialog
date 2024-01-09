<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Location;

use App\Application\CommandInterface;
use App\Domain\Regulation\LocationNew;
use App\Domain\Regulation\Measure;

final class SaveRegulationLocationCommand implements CommandInterface
{
    public ?string $cityCode;
    public ?string $cityLabel;
    public ?string $roadName;
    public ?string $fromHouseNumber;
    public ?string $toHouseNumber;
    public ?string $geometry;

    public function __construct(
        public ?Measure $measure = null,
        public readonly ?LocationNew $location = null,
    ) {
    }

    public static function create(
        Measure $measure,
        LocationNew $location = null,
    ): self {
        $command = new self($measure, $location);
        $command->cityCode = $location?->getCityCode();
        $command->cityLabel = $location?->getCityLabel();
        $command->roadName = $location?->getRoadName();
        $command->fromHouseNumber = $location?->getFromHouseNumber();
        $command->toHouseNumber = $location?->getToHouseNumber();

        return $command;
    }
}

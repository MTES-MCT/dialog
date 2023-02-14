<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Condition\Location;
use App\Domain\Regulation\RegulationOrder;

final class SaveRegulationStep2Command implements CommandInterface
{
    public ?string $postalCode;
    public ?string $city;
    public ?string $roadName;
    public ?string $fromHouseNumber;
    public ?string $toHouseNumber;

    public function __construct(
        public readonly RegulationOrder $regulationOrder,
        public readonly ?Location $location = null,
    ) {
    }

    public static function create(
        RegulationOrder $regulationOrder,
        Location $location = null,
    ): self {
        $command = new self($regulationOrder, $location);

        $command->postalCode = $location?->getPostalCode();
        $command->city = $location?->getCity();
        $command->roadName = $location?->getRoadName();
        $command->fromHouseNumber = $location?->getFromHouseNumber();
        $command->toHouseNumber = $location?->getToHouseNumber();

        return $command;
    }
}

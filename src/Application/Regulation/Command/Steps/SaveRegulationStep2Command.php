<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrderRecord;

final class SaveRegulationStep2Command implements CommandInterface
{
    public ?string $postalCode;
    public ?string $city;
    public ?string $roadName;
    public ?string $fromHouseNumber;
    public ?string $toHouseNumber;

    public function __construct(
        public readonly RegulationOrderRecord $regulationOrderRecord,
        public readonly ?Location $location = null,
    ) {
    }

    public static function create(
        RegulationOrderRecord $regulationOrderRecord,
        Location $location = null,
    ): self {
        $command = new self($regulationOrderRecord, $location);

        $command->postalCode = $location?->getPostalCode();
        $command->city = $location?->getCity();
        $command->roadName = $location?->getRoadName();
        $command->fromHouseNumber = $location?->getFromHouseNumber();
        $command->toHouseNumber = $location?->getToHouseNumber();

        return $command;
    }
}

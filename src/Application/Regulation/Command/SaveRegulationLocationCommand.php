<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrderRecord;

final class SaveRegulationLocationCommand implements CommandInterface
{
    public ?string $address;
    public ?string $fromHouseNumber;
    public ?string $toHouseNumber;
    public array $measures = [];

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
        $command->address = $location?->getAddress();
        $command->fromHouseNumber = $location?->getFromHouseNumber();
        $command->toHouseNumber = $location?->getToHouseNumber();

        if ($location) {
            foreach ($location->getMeasures() as $measure) {
                array_push(
                    $command->measures,
                    new SaveMeasureCommand($measure),
                );
            }
        }

        return $command;
    }
}

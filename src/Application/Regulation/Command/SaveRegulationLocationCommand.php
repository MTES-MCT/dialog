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
    public ?string $fromPoint;
    public ?string $toPoint;
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
                $command->measures[] = new SaveMeasureCommand($measure);
            }
        } else {
            $command->measures[] = new SaveMeasureCommand();
        }

        return $command;
    }
}

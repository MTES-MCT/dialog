<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Condition\VehicleCharacteristics;
use App\Domain\Regulation\RegulationOrderRecord;

final class SaveRegulationStep4Command implements CommandInterface
{
    public ?float $maxWeight = null;
    public ?float $maxHeight = null;
    public ?float $maxWidth = null;
    public ?float $maxLength = null;

    public function __construct(
        public readonly RegulationOrderRecord $regulationOrderRecord,
        public readonly ?VehicleCharacteristics $vehicleCharacteristics = null,
    ) {
    }

    public static function create(
        RegulationOrderRecord $regulationOrderRecord,
        VehicleCharacteristics $vehicleCharacteristics = null,
    ): self {
        $command = new self($regulationOrderRecord, $vehicleCharacteristics);
        $command->maxHeight = $vehicleCharacteristics?->getMaxHeight();
        $command->maxLength = $vehicleCharacteristics?->getMaxLength();
        $command->maxWeight = $vehicleCharacteristics?->getMaxWeight();
        $command->maxWidth = $vehicleCharacteristics?->getMaxWidth();

        return $command;
    }
}

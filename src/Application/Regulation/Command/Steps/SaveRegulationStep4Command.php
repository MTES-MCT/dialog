<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\Steps;

use App\Application\CommandInterface;
use App\Domain\Condition\VehicleCharacteristics;
use App\Domain\Regulation\RegulationOrderRecord;

final class SaveRegulationStep4Command implements CommandInterface
{
    public function __construct(
        public readonly RegulationOrderRecord $regulationOrderRecord,
        public readonly ?VehicleCharacteristics $vehicleCharacteristics = null,
    ) {
    }

    public ?float $maxWeight = null;
    public ?float $maxHeight = null;
    public ?float $maxWidth = null;
    public ?float $maxLength = null;
}

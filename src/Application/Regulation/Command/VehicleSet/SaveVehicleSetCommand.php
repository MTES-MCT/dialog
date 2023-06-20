<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command\VehicleSet;

use App\Application\CommandInterface;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Measure;

final class SaveVehicleSetCommand implements CommandInterface
{
    public ?bool $allVehicles;
    public array $restrictedTypes;
    public ?string $otherRestrictedTypeText;
    public array $exemptedTypes;
    public ?string $otherExemptedTypeText;
    public ?Measure $measure;

    public function __construct(
        public readonly ?VehicleSet $vehicleSet = null,
    ) {
        $this->initFromEntity($vehicleSet);
    }

    public function initFromEntity(?VehicleSet $vehicleSet): self
    {
        $this->allVehicles = $vehicleSet ? empty($vehicleSet->getRestrictedTypes()) : null;
        $this->restrictedTypes = $vehicleSet?->getRestrictedTypes() ?? [];
        $this->otherRestrictedTypeText = $vehicleSet?->getOtherRestrictedTypeText();
        $this->exemptedTypes = $vehicleSet?->getExemptedTypes() ?? [];
        $this->otherExemptedTypeText = $vehicleSet?->getOtherExemptedTypeText();

        return $this;
    }

    public function cleanTypes(): void
    {
        if ($this->allVehicles) {
            $this->restrictedTypes = [];
            $this->otherRestrictedTypeText = null;
        }

        if (!\in_array(VehicleTypeEnum::OTHER->value, $this->restrictedTypes)) {
            $this->otherRestrictedTypeText = null;
        }

        if (!\in_array(VehicleTypeEnum::OTHER->value, $this->exemptedTypes)) {
            $this->otherExemptedTypeText = null;
        }
    }
}

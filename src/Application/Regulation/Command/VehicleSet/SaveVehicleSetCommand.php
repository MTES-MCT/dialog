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
    public array $critairTypes;
    public array $restrictedTypes;
    public ?float $heavyweightMaxWeight;
    public ?float $heavyweightMaxWidth;
    public ?float $heavyweightMaxLength;
    public ?float $heavyweightMaxHeight;
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
        $this->heavyweightMaxWeight = $vehicleSet?->getHeavyweightMaxWeight();
        $this->heavyweightMaxWidth = $vehicleSet?->getHeavyweightMaxWidth();
        $this->heavyweightMaxLength = $vehicleSet?->getHeavyweightMaxLength();
        $this->heavyweightMaxHeight = $vehicleSet?->getHeavyweightMaxHeight();
        $this->critairTypes = $vehicleSet?->getCritairTypes() ?? [];

        return $this;
    }

    public function clean(): void
    {
        if ($this->allVehicles) {
            $this->restrictedTypes = [];
            $this->otherRestrictedTypeText = null;
            $this->heavyweightMaxWeight = null;
            $this->heavyweightMaxWidth = null;
            $this->heavyweightMaxLength = null;
            $this->heavyweightMaxHeight = null;
        }

        if (!\in_array(VehicleTypeEnum::OTHER->value, $this->restrictedTypes)) {
            $this->otherRestrictedTypeText = null;
        }

        if (!\in_array(VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, $this->restrictedTypes)) {
            $this->heavyweightMaxWeight = null;
            $this->heavyweightMaxWidth = null;
            $this->heavyweightMaxLength = null;
            $this->heavyweightMaxHeight = null;
        }

        if ($this->heavyweightMaxWeight === 0.0) {
            $this->heavyweightMaxWeight = null;
        }

        if ($this->heavyweightMaxWidth === 0.0) {
            $this->heavyweightMaxWidth = null;
        }

        if ($this->heavyweightMaxLength === 0.0) {
            $this->heavyweightMaxLength = null;
        }

        if ($this->heavyweightMaxHeight === 0.0) {
            $this->heavyweightMaxHeight = null;
        }

        if (!\in_array(VehicleTypeEnum::CRITAIR->value, $this->restrictedTypes)) {
            $this->critairTypes = [];
        }

        if (!\in_array(VehicleTypeEnum::OTHER->value, $this->exemptedTypes)) {
            $this->otherExemptedTypeText = null;
        }
    }
}

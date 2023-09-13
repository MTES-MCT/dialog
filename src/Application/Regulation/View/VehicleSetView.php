<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\VehicleTypeEnum;

class VehicleSetView
{
    public function __construct(
        public readonly array $restrictedTypes,
        public readonly array $exemptedTypes,
        public readonly array $heavyweightCharacteristics,
    ) {
    }

    public static function fromEntity(?VehicleSet $vehicleSet): self
    {
        if (!$vehicleSet) {
            return new self([], [], []);
        }

        $restrictedTypes = [];

        foreach ($vehicleSet->getRestrictedTypes() as $vehicleType) {
            if (!\in_array($vehicleType, [VehicleTypeEnum::OTHER->value, VehicleTypeEnum::CRITAIR->value])) {
                $restrictedTypes[] = ['name' => $vehicleType];
            }
        }

        if ($vehicleSet->getOtherRestrictedTypeText()) {
            $restrictedTypes[] = ['name' => $vehicleSet->getOtherRestrictedTypeText(), 'isOther' => true];
        }

        foreach ($vehicleSet->getCritairTypes() as $critair) {
            $restrictedTypes[] = ['name' => $critair];
        }

        $exemptedTypes = [];

        foreach ($vehicleSet->getExemptedTypes() as $vehicleType) {
            if ($vehicleType !== VehicleTypeEnum::OTHER->value) {
                $exemptedTypes[] = ['name' => $vehicleType];
            }
        }

        if ($vehicleSet->getOtherExemptedTypeText()) {
            $exemptedTypes[] = ['name' => $vehicleSet->getOtherExemptedTypeText(), 'isOther' => true];
        }

        $heavyweightCharacteristics = [];

        if ($vehicleSet->getHeavyweightMaxWeight()) {
            $heavyweightCharacteristics[] = [
                'name' => 'weight',
                'value' => $vehicleSet->getHeavyweightMaxWeight(),
                'unit' => 'tons',
                'suffix' => false,
            ];
        }

        if ($vehicleSet->getHeavyweightMaxWidth()) {
            $heavyweightCharacteristics[] = [
                'name' => 'width',
                'value' => $vehicleSet->getHeavyweightMaxWidth(),
                'unit' => 'meters',
                'suffix' => true,
            ];
        }

        if ($vehicleSet->getHeavyweightMaxLength()) {
            $heavyweightCharacteristics[] = [
                'name' => 'length',
                'value' => $vehicleSet->getHeavyweightMaxLength(),
                'unit' => 'meters',
                'suffix' => true,
            ];
        }

        if ($vehicleSet->getHeavyweightMaxHeight()) {
            $heavyweightCharacteristics[] = [
                'name' => 'height',
                'value' => $vehicleSet->getHeavyweightMaxHeight(),
                'unit' => 'meters',
                'suffix' => true,
            ];
        }

        return new self(
            $restrictedTypes,
            $exemptedTypes,
            $heavyweightCharacteristics,
        );
    }
}

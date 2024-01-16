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
        public readonly array $maxCharacteristics,
    ) {
    }

    public static function fromEntity(?VehicleSet $vehicleSet): self
    {
        if (!$vehicleSet) {
            return new self([], [], []);
        }

        $restrictedTypes = [];

        foreach ($vehicleSet->getRestrictedTypes() as $vehicleType) {
            if (
                !\in_array($vehicleType, [
                    VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value,
                    VehicleTypeEnum::DIMENSIONS->value,
                    VehicleTypeEnum::CRITAIR->value,
                    VehicleTypeEnum::OTHER->value,
                ])
            ) {
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

        $maxCharacteristics = [];

        if ($vehicleSet->getHeavyweightMaxWeight()) {
            $maxCharacteristics[] = [
                'name' => 'weight',
                'value' => $vehicleSet->getHeavyweightMaxWeight(),
            ];
        }

        if ($vehicleSet->getMaxWidth()) {
            $maxCharacteristics[] = [
                'name' => 'width',
                'value' => $vehicleSet->getMaxWidth(),
            ];
        }

        if ($vehicleSet->getMaxLength()) {
            $maxCharacteristics[] = [
                'name' => 'length',
                'value' => $vehicleSet->getMaxLength(),
            ];
        }

        if ($vehicleSet->getMaxHeight()) {
            $maxCharacteristics[] = [
                'name' => 'height',
                'value' => $vehicleSet->getMaxHeight(),
            ];
        }

        return new self(
            $restrictedTypes,
            $exemptedTypes,
            $maxCharacteristics,
        );
    }
}

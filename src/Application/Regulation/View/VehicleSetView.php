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
        public readonly ?float $heavyweightMaxWeight,
        public readonly array $dimensions,
    ) {
    }

    public static function fromEntity(?VehicleSet $vehicleSet): self
    {
        if (!$vehicleSet) {
            return new self([], [], null, []);
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

        $heavyweightMaxWeight = $vehicleSet->getHeavyweightMaxWeight();

        $dimensions = [];

        if ($vehicleSet->getMaxWidth()) {
            $dimensions[] = [
                'name' => 'width',
                'value' => $vehicleSet->getMaxWidth(),
            ];
        }

        if ($vehicleSet->getMaxLength()) {
            $dimensions[] = [
                'name' => 'length',
                'value' => $vehicleSet->getMaxLength(),
            ];
        }

        if ($vehicleSet->getMaxHeight()) {
            $dimensions[] = [
                'name' => 'height',
                'value' => $vehicleSet->getMaxHeight(),
            ];
        }

        return new self(
            $restrictedTypes,
            $exemptedTypes,
            $heavyweightMaxWeight,
            $dimensions,
        );
    }
}

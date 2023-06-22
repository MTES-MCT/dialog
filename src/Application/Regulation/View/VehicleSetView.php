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
    ) {
    }

    public static function fromEntity(?VehicleSet $vehicleSet): self
    {
        if (!$vehicleSet) {
            return new self([], []);
        }

        $restrictedTypes = [];

        foreach ($vehicleSet->getRestrictedTypes() as $vehicleType) {
            if ($vehicleType !== VehicleTypeEnum::OTHER->value) {
                $restrictedTypes[] = ['name' => $vehicleType];
            }
        }

        if ($vehicleSet->getOtherRestrictedTypeText()) {
            $restrictedTypes[] = ['name' => $vehicleSet->getOtherRestrictedTypeText(), 'isOther' => true];
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

        return new self($restrictedTypes, $exemptedTypes);
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\Factory;

use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Infrastructure\DTO\Event\SaveVehicleSetDTO;

final class VehicleSetCommandFactory
{
    public function fromDto(?SaveVehicleSetDTO $dto): ?SaveVehicleSetCommand
    {
        if (!$dto) {
            return null;
        }

        $cmd = new SaveVehicleSetCommand();
        $cmd->allVehicles = $dto->allVehicles;
        $cmd->restrictedTypes = $dto->restrictedTypes ?? [];
        $cmd->exemptedTypes = $dto->exemptedTypes ?? [];
        $cmd->otherRestrictedTypeText = $dto->otherRestrictedTypeText;
        $cmd->otherExemptedTypeText = $dto->otherExemptedTypeText;
        $cmd->heavyweightMaxWeight = $dto->heavyweightMaxWeight;
        $cmd->maxWidth = $dto->maxWidth;
        $cmd->maxLength = $dto->maxLength;
        $cmd->maxHeight = $dto->maxHeight;
        $cmd->critairTypes = $dto->critairTypes ?? [];

        return $cmd;
    }
}

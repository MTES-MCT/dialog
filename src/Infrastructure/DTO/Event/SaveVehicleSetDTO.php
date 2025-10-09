<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use Symfony\Component\ObjectMapper\Attribute\Map;

#[Map(target: SaveVehicleSetCommand::class)]
final class SaveVehicleSetDTO
{
    public ?bool $allVehicles = null;
    public ?array $critairTypes = [];
    public ?array $restrictedTypes = [];
    public ?float $heavyweightMaxWeight = null;
    public ?float $maxWidth = null;
    public ?float $maxLength = null;
    public ?float $maxHeight = null;
    public ?string $otherRestrictedTypeText = null;
    public ?array $exemptedTypes = [];
    public ?string $otherExemptedTypeText = null;
}

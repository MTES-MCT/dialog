<?php

declare(strict_types=1);

namespace App\Infrastructure\DTO\Event;

final class SaveVehicleSetDTO
{
    public ?bool $allVehicles = null;
    /** @var string[]|null */
    public ?array $critairTypes = null;
    /** @var string[]|null */
    public ?array $restrictedTypes = null;
    public ?float $heavyweightMaxWeight = null;
    public ?float $maxWidth = null;
    public ?float $maxLength = null;
    public ?float $maxHeight = null;
    public ?string $otherRestrictedTypeText = null;
    /** @var string[]|null */
    public ?array $exemptedTypes = null;
    public ?string $otherExemptedTypeText = null;
}

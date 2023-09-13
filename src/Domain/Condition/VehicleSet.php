<?php

declare(strict_types=1);

namespace App\Domain\Condition;

use App\Domain\Regulation\Measure;

class VehicleSet
{
    public const DEFAULT_MAX_WEIGHT = 3.5;

    public function __construct(
        private string $uuid,
        private Measure $measure,
        private array $restrictedTypes = [],
        private ?string $otherRestrictedTypeText = null,
        private array $exemptedTypes = [],
        private ?string $otherExemptedTypeText = null,
        private ?float $heavyweightMaxWeight = null,
        private ?float $heavyweightMaxWidth = null,
        private ?float $heavyweightMaxLength = null,
        private ?float $heavyweightMaxHeight = null,
        private ?array $critairTypes = [],
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getMeasure(): Measure
    {
        return $this->measure;
    }

    public function getRestrictedTypes(): array
    {
        return $this->restrictedTypes;
    }

    public function getCritairTypes(): array
    {
        return $this->critairTypes ?? [];
    }

    public function getOtherRestrictedTypeText(): ?string
    {
        return $this->otherRestrictedTypeText;
    }

    public function getExemptedTypes(): array
    {
        return $this->exemptedTypes;
    }

    public function getOtherExemptedTypeText(): ?string
    {
        return $this->otherExemptedTypeText;
    }

    public function getHeavyweightMaxWeight(): ?float
    {
        return $this->heavyweightMaxWeight;
    }

    public function getHeavyweightMaxWidth(): ?float
    {
        return $this->heavyweightMaxWidth;
    }

    public function getHeavyweightMaxLength(): ?float
    {
        return $this->heavyweightMaxLength;
    }

    public function getHeavyweightMaxHeight(): ?float
    {
        return $this->heavyweightMaxHeight;
    }

    public function update(
        array $restrictedTypes = null,
        string $otherRestrictedTypeText = null,
        array $exemptedTypes = null,
        string $otherExemptedTypeText = null,
        float $heavyweightMaxWeight = null,
        float $heavyweightMaxWidth = null,
        float $heavyweightMaxLength = null,
        float $heavyweightMaxHeight = null,
        array $critairTypes = null,
    ): void {
        $this->restrictedTypes = $restrictedTypes ?: [];
        $this->otherRestrictedTypeText = $otherRestrictedTypeText;
        $this->exemptedTypes = $exemptedTypes ?: [];
        $this->otherExemptedTypeText = $otherExemptedTypeText;
        $this->heavyweightMaxWeight = $heavyweightMaxWeight;
        $this->heavyweightMaxWidth = $heavyweightMaxWidth;
        $this->heavyweightMaxLength = $heavyweightMaxLength;
        $this->heavyweightMaxHeight = $heavyweightMaxHeight;
        $this->critairTypes = $critairTypes ?? [];
    }
}

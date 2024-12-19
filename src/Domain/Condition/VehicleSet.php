<?php

declare(strict_types=1);

namespace App\Domain\Condition;

use App\Domain\Regulation\Measure;

class VehicleSet
{
    public function __construct(
        private string $uuid,
        private Measure $measure,
        private array $restrictedTypes = [],
        private ?string $otherRestrictedTypeText = null,
        private array $exemptedTypes = [],
        private ?string $otherExemptedTypeText = null,
        private ?float $heavyweightMaxWeight = null,
        private ?float $maxWidth = null,
        private ?float $maxLength = null,
        private ?float $maxHeight = null,
        /** @var string[] */
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
        if (!$this->critairTypes) {
            return [];
        }

        $critairTypes = $this->critairTypes;
        sort($critairTypes);

        return $critairTypes;
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

    public function getMaxWidth(): ?float
    {
        return $this->maxWidth;
    }

    public function getMaxLength(): ?float
    {
        return $this->maxLength;
    }

    public function getMaxHeight(): ?float
    {
        return $this->maxHeight;
    }

    public function update(
        ?array $restrictedTypes = null,
        ?string $otherRestrictedTypeText = null,
        ?array $exemptedTypes = null,
        ?string $otherExemptedTypeText = null,
        ?float $heavyweightMaxWeight = null,
        ?float $maxWidth = null,
        ?float $maxLength = null,
        ?float $maxHeight = null,
        ?array $critairTypes = null,
    ): void {
        $this->restrictedTypes = $restrictedTypes ?: [];
        $this->otherRestrictedTypeText = $otherRestrictedTypeText;
        $this->exemptedTypes = $exemptedTypes ?: [];
        $this->otherExemptedTypeText = $otherExemptedTypeText;
        $this->heavyweightMaxWeight = $heavyweightMaxWeight;
        $this->maxWidth = $maxWidth;
        $this->maxLength = $maxLength;
        $this->maxHeight = $maxHeight;
        $this->critairTypes = $critairTypes ?? [];
    }
}

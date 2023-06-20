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

    public function update(
        array $restrictedTypes = null,
        string $otherRestrictedTypeText = null,
        array $exemptedTypes = null,
        string $otherExemptedTypeText = null,
    ): void {
        $this->restrictedTypes = $restrictedTypes ?: [];
        $this->otherRestrictedTypeText = $otherRestrictedTypeText;
        $this->exemptedTypes = $exemptedTypes ?: [];
        $this->otherExemptedTypeText = $otherExemptedTypeText;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Condition\RegulationCondition;

class RegulationOrder
{
    private ?RegulationCondition $regulationCondition = null;

    public function __construct(
        private string $uuid,
        private string $issuingAuthority,
        private string $description,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getIssuingAuthority(): string
    {
        return $this->issuingAuthority;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRegulationCondition(): ?RegulationCondition
    {
        return $this->regulationCondition;
    }

    public function setRegulationCondition(RegulationCondition $regulationCondition): void
    {
        $this->regulationCondition = $regulationCondition;
    }

    public function update(
        string $issuingAuthority,
        string $description,
    ): void {
        $this->issuingAuthority = $issuingAuthority;
        $this->description = $description;
    }
}

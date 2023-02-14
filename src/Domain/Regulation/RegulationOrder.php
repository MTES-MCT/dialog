<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Condition\RegulationCondition;

class RegulationOrder
{
    public function __construct(
        private string $uuid,
        private string $issuingAuthority,
        private string $description,
        private RegulationOrderRecord $regulationOrderRecord,
        private RegulationCondition $regulationCondition,
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

    public function getRegulationOrderRecord(): RegulationOrderRecord
    {
        return $this->regulationOrderRecord;
    }

    public function getRegulationCondition(): RegulationCondition
    {
        return $this->regulationCondition;
    }

    public function update(
        string $issuingAuthority,
        string $description,
    ): void {
        $this->issuingAuthority = $issuingAuthority;
        $this->description = $description;
    }
}

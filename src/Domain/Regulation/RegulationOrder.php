<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Condition\RegulationCondition;

class RegulationOrder
{
    public function __construct(
        private string $uuid,
        private string $description,
        private string $issuingAuthority,
        private RegulationCondition $regulationCondition,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIssuingAuthority(): string
    {
        return $this->issuingAuthority;
    }

    public function getRegulationCondition(): RegulationCondition
    {
        return $this->regulationCondition;
    }
}

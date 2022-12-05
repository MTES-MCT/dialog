<?php

declare(strict_types=1);

namespace App\Domain\RegulationOrder;

use App\Domain\Condition\RegulationCondition;

class RegulationOrder
{
    private ?RegulationCondition $regulationCondition = null;

    public function __construct(
        private string $uuid,
        private string $description,
        private string $issuingAuthority,
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

    public function getRegulationCondition(): ?RegulationCondition
    {
        return $this->regulationCondition;
    }
}

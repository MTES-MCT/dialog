<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\Condition\RegulationCondition;

class RegulationOrder
{
    private ?RegulationCondition $regulationCondition = null;
    private iterable $locations;

    public function __construct(
        private string $uuid,
        private string $issuingAuthority,
        private string $description,
        private ?\DateTimeInterface $startDate,
        private ?\DateTimeInterface $endDate = null,
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function getRegulationCondition(): ?RegulationCondition
    {
        return $this->regulationCondition;
    }

    public function setRegulationCondition(RegulationCondition $regulationCondition): void
    {
        $this->regulationCondition = $regulationCondition;
    }

    public function getLocations(): iterable
    {
        return $this->locations;
    }

    public function update(
        string $issuingAuthority,
        string $description,
        \DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate = null,
    ): void {
        $this->issuingAuthority = $issuingAuthority;
        $this->description = $description;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }
}

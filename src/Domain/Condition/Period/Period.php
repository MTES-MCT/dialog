<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

class Period
{
    private ?OverallPeriod $overallValidPeriod = null;
    private ?OverallPeriod $overallExceptionPeriod = null;

    public function __construct(
        private string $uuid,
        private ?string $name = null,
        private ?\DateTimeInterface $startDate = null,
        private ?\DateTimeInterface $endDate = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function getOverallValidPeriod(): ?OverallPeriod
    {
        return $this->overallValidPeriod;
    }

    public function getOverallExceptionPeriod(): ?OverallPeriod
    {
        return $this->overallExceptionPeriod;
    }
}

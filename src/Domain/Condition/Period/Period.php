<?php

declare(strict_types=1);

namespace App\Domain\Condition\Period;

use App\Domain\Regulation\Measure;

class Period
{
    public function __construct(
        private string $uuid,
        private Measure $measure,
        private \DateTimeInterface $startTime,
        private \DateTimeInterface $endTime,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function getMeasure(): Measure
    {
        return $this->measure;
    }

    public function update(
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
    ): void {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }
}

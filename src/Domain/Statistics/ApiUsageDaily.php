<?php

declare(strict_types=1);

namespace App\Domain\Statistics;

class ApiUsageDaily
{
    public function __construct(
        private string $uuid,
        private \DateTimeInterface $day,
        private string $type,
        private int $count = 0,
        private ?\DateTimeInterface $exportedAt = null,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getDay(): \DateTimeInterface
    {
        return $this->day;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    public function getExportedAt(): ?\DateTimeInterface
    {
        return $this->exportedAt;
    }

    public function setExportedAt(?\DateTimeInterface $exportedAt): void
    {
        $this->exportedAt = $exportedAt;
    }
}

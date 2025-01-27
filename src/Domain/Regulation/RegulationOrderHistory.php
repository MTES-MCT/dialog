<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

class RegulationOrderHistory
{
    public function __construct(
        private string $uuid,
        private string $regulationOrderUuid,
        private string $userUuid,
        private string $action,
        private \DateTimeInterface $date,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRegulationOrderUuid(): string
    {
        return $this->regulationOrderUuid;
    }

    public function getUserUuid(): string
    {
        return $this->userUuid;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\User\User;

class RegulationOrderHistory
{
    public function __construct(
        private string $uuid,
        private RegulationOrder $regulationOrder,
        private User $user,
        private string $action,
        private \DateTimeInterface $date,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }

    public function getUser(): User
    {
        return $this->user;
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

<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

use App\Domain\User\Organization;

class RegulationOrderRecord
{
    public function __construct(
        private string $uuid,
        private string $status,
        private RegulationOrder $regulationOrder,
        private \DateTimeInterface $createdAt,
        private Organization $organization,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getOrganization(): ?Organization
    {
        return $this->organization;
    }

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
    }
}

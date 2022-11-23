<?php

declare(strict_types=1);

namespace App\Domain\RegulationOrder;

use App\Domain\RegulationOrder\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\User\Organization;

class RegulationOrderRecord
{
    public function __construct(
        private string $uuid,
        private RegulationOrderRecordStatusEnum $status,
        private Organization $organization,
        private RegulationOrder $regulationOrder,
        private \DateTimeImmutable $createdAt,
    ) {
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getStatus(): RegulationOrderRecordStatusEnum
    {
        return $this->status;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getRegulationOrder(): RegulationOrder
    {
        return $this->regulationOrder;
    }
}

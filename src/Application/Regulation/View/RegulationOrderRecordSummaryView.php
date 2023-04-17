<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\User\OrganizationRegulationAccessInterface;

class RegulationOrderRecordSummaryView implements OrganizationRegulationAccessInterface
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $identifier,
        public readonly string $organizationUuid,
        public readonly string $organizationName,
        public readonly string $status,
        public readonly string $description,
        public readonly ?\DateTimeInterface $startDate,
        public readonly ?\DateTimeInterface $endDate,
        public readonly ?DetailLocationView $location,
    ) {
    }

    public function getOrganizationUuid(): ?string
    {
        return $this->organizationUuid;
    }
}

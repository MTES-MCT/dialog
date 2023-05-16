<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Regulation\RegulationLocationsInterface;
use App\Domain\User\OrganizationRegulationAccessInterface;

class RegulationOrderRecordSummaryView implements OrganizationRegulationAccessInterface, RegulationLocationsInterface
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $identifier,
        public readonly string $organizationUuid,
        public readonly string $organizationName,
        public readonly string $status,
        public readonly string $category,
        public readonly ?string $otherCategoryText,
        public readonly string $description,
        public readonly array $locations,
        public readonly ?\DateTimeInterface $startDate,
        public readonly ?\DateTimeInterface $endDate,
    ) {
    }

    public function getOrganizationUuid(): ?string
    {
        return $this->organizationUuid;
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function countLocations(): int
    {
        return \count($this->locations);
    }
}

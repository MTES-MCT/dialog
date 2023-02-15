<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\User\OrganizationRegulationAccessInterface;

class RegulationOrderRecordSummaryView implements OrganizationRegulationAccessInterface
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $organizationUuid,
        public readonly string $status,
        public readonly string $description,
        public readonly ?PeriodView $period,
        public readonly ?DetailLocationView $location,
        public readonly ?VehicleCharacteristicsView $vehicleCharacteristics,
    ) {
    }

    public function getOrganizationUuid(): ?string
    {
        return $this->organizationUuid;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

class RegulationOrderRecordSummaryView
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
}

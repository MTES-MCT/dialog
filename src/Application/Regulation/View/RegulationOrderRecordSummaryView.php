<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class RegulationOrderRecordSummaryView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $status,
        public readonly string $description,
        public readonly ?PeriodView $period,
        public readonly ?ListItemLocationView $location,
        public readonly ?VehicleCharacteristicsView $vehicleCharacteristics,
    ) {
    }
}

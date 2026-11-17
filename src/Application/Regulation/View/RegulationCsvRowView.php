<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class RegulationCsvRowView
{
    /**
     * @param string[] $excludedPublicHolidays Dates Y-m-d
     */
    public function __construct(
        public string $regulationOrderUuid,
        public string $title,
        public string $category,
        public string $status,
        public ?\DateTimeInterface $startDate,
        public ?\DateTimeInterface $endDate,
        public string $organizationName,
        public string $organizationSiret,
        public string $linkPdf,
        public string $measureUuid,
        public string $measureType,
        public string $locationUuid,
        public string $locationType,
        public string $locationLabel,
        public string $geometry,
        public ?VehicleSetView $vehicleSet = null,
        public bool $isPublicHolidaysExcluded = false,
        public array $excludedPublicHolidays = [],
        // Bornes de l'emprise (point A et point B) et sens de circulation concerné, relatif à ces bornes
        public string $locationStart = '',
        public string $locationEnd = '',
        public ?string $direction = null,
    ) {
    }
}

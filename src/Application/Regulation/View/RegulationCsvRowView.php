<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class RegulationCsvRowView
{
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
    ) {
    }
}

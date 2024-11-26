<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class RegulationOrderDatexListItemView
{
    public function __construct(
        public string $uuid,
        public string $regulationOrderRecordUuid,
        public string $regulationId,
        public string $organization,
        public string $source,
        public string $title,
        public ?\DateTimeInterface $startDate,
        public ?\DateTimeInterface $endDate,
        public array $trafficRegulations,
    ) {
    }
}

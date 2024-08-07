<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class RegulationOrderListItemView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $identifier,
        public readonly string $status,
        public readonly int $numLocations,
        public readonly string $organizationName,
        public readonly string $organizationUuid,
        public readonly ?LocationViewInterface $location,
        public readonly ?\DateTimeInterface $startDate,
        public readonly ?\DateTimeInterface $endDate,
    ) {
    }
}

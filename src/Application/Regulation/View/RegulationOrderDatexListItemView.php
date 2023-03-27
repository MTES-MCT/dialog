<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class RegulationOrderDatexListItemView
{
    public function __construct(
        public readonly string $uuid,
        public readonly string $organization,
        public readonly string $description,
        public readonly ?\DateTimeInterface $startDate,
        public readonly ?\DateTimeInterface $endDate,
        public readonly ?DatexLocationView $location,
    ) {
    }
}

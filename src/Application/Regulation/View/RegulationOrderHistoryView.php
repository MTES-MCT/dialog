<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final class RegulationOrderHistoryView
{
    public function __construct(
        public readonly \DateTimeInterface $createdAt,
        public readonly ?\DateTimeInterface $updatedAt,
        public readonly ?\DateTimeInterface $publishedAt,
    ) {
    }
}

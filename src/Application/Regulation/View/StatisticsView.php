<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class StatisticsView
{
    public function __construct(
        public int $users,
        public int $organizations,
        public int $totalRegulationOrderRecords,
        public int $publishedRegulationOrderRecords,
        public int $permanentRegulationOrderRecords,
        public int $temporaryRegulationOrderRecords,
    ) {
    }
}

<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

final readonly class CifsIncidentView
{
    public function __construct(
        public string $id,
        public string $creationTime,
        public string $type,
        public string $street,
        public string $direction,
        public string $polyline,
        public string $startTime,
        public string $endTime,
        public string $sourceReference,
        public string $sourceName,
    ) {
    }
}

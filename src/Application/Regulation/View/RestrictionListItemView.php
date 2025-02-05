<?php

declare(strict_types=1);

namespace App\Application\Regulation\View;

use App\Domain\Geography\Coordinates;

final readonly class RestrictionListItemView
{
    public function __construct(
        public string $regulationOrderRecordUuid,
        public string $measureType,
        public string $label,
        public Coordinates $centroid,
    ) {
    }
}

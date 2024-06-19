<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;

final readonly class GetRawGeoJSONGeometryQuery implements QueryInterface
{
    public function __construct(
        public string $geometry,
    ) {
    }
}

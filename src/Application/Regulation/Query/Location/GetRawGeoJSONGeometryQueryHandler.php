<?php

declare(strict_types=1);

namespace App\Application\Regulation\Query\Location;

use App\Application\QueryInterface;

final class GetRawGeoJSONGeometryQueryHandler implements QueryInterface
{
    public function __construct(
    ) {
    }

    public function __invoke(GetRawGeoJSONGeometryQuery $query): string
    {
        return $query->geometry;
    }
}

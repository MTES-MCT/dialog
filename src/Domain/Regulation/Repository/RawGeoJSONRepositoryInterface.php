<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Repository;

use App\Domain\Regulation\Location\RawGeoJSON;

interface RawGeoJSONRepositoryInterface
{
    public function add(RawGeoJSON $rawGeoJSON): RawGeoJSON;

    public function delete(RawGeoJSON $rawGeoJSON): void;
}

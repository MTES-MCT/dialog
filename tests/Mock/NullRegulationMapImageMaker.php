<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Domain\Regulation\RegulationMapImage;
use App\Domain\Regulation\RegulationMapImageMakerInterface;

final class NullRegulationMapImageMaker implements RegulationMapImageMakerInterface
{
    public function make(string $regulationOrderRecordUuid): ?RegulationMapImage
    {
        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Domain\Regulation\RegulationMapImageMakerInterface;

final class NullRegulationMapImageMaker implements RegulationMapImageMakerInterface
{
    public function makeBase64Jpeg(string $regulationOrderRecordUuid): ?string
    {
        return null;
    }
}

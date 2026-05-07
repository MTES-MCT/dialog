<?php

declare(strict_types=1);

namespace App\Domain\Regulation;

interface RegulationMapImageMakerInterface
{
    public function makeBase64Jpeg(string $regulationOrderRecordUuid): ?string;
}

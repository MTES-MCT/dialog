<?php

declare(strict_types=1);

namespace App\Domain\Regulation\Specification;

use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;

/**
 * Les arrêtés issus de Litteralis (Sogelink) ou de l'API ne sont pas modifiables dans DiaLog.
 */
final class CanEditRegulationOrderRecord
{
    public function isSatisfiedBy(string $source): bool
    {
        return !\in_array(
            $source,
            [
                RegulationOrderRecordSourceEnum::LITTERALIS->value,
                RegulationOrderRecordSourceEnum::API->value,
            ],
            true,
        );
    }
}

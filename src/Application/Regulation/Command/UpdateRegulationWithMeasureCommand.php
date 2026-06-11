<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Infrastructure\DTO\Event\SaveMeasureDTO;

final class UpdateRegulationWithMeasureCommand implements CommandInterface
{
    /** @param SaveMeasureDTO[]|null $measureDtos */
    public function __construct(
        public SaveRegulationGeneralInfoCommand $generalInfo,
        public RegulationOrderRecordStatusEnum $status,
        public ?array $measureDtos,
    ) {
    }
}

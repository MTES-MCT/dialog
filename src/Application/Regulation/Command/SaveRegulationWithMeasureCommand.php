<?php

declare(strict_types=1);

namespace App\Application\Regulation\Command;

use App\Application\CommandInterface;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Infrastructure\DTO\Event\SaveMeasureDTO;

final class SaveRegulationWithMeasureCommand implements CommandInterface
{
    public function __construct(
        public SaveRegulationGeneralInfoCommand $generalInfo,
        public RegulationOrderRecordStatusEnum $status,
        /** @var SaveMeasureDTO[] */
        public ?array $measureDtos,
    ) {
    }
}

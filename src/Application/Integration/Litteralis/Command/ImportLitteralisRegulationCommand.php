<?php

declare(strict_types=1);

namespace App\Application\Integration\Litteralis\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;

final class ImportLitteralisRegulationCommand implements CommandInterface
{
    public function __construct(
        public SaveRegulationGeneralInfoCommand $generalInfoCommand,
        /** @var SaveMeasureCommand[] */
        public readonly array $measureCommands,
    ) {
        $generalInfoCommand->source = RegulationOrderRecordSourceEnum::LITTERALIS->value;
    }
}

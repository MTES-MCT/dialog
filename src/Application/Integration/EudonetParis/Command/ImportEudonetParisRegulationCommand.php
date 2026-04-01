<?php

declare(strict_types=1);

namespace App\Application\Integration\EudonetParis\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;

final class ImportEudonetParisRegulationCommand implements CommandInterface
{
    /**
     * @param SaveMeasureCommand[] $measureCommands
     */
    public function __construct(
        public readonly SaveRegulationGeneralInfoCommand $generalInfoCommand,
        public readonly array $measureCommands,
    ) {
        $generalInfoCommand->source = RegulationOrderRecordSourceEnum::EUDONET_PARIS->value;
    }
}

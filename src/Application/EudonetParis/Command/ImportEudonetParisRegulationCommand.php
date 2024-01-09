<?php

declare(strict_types=1);

namespace App\Application\EudonetParis\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\Location\SaveRegulationLocationCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;

final class ImportEudonetParisRegulationCommand implements CommandInterface
{
    public function __construct(
        public readonly SaveRegulationGeneralInfoCommand $generalInfoCommand,
        /** @var SaveRegulationLocationCommand[] */
        public readonly array $locationCommands,
    ) {
        $generalInfoCommand->source = RegulationOrderRecordSourceEnum::EUDONET_PARIS->value;
    }
}

<?php

declare(strict_types=1);

namespace App\Application\BacIdf\Command;

use App\Application\CommandInterface;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\SaveRegulationLocationCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;

final class ImportBacIdfRegulationCommand implements CommandInterface
{
    public function __construct(
        public SaveRegulationGeneralInfoCommand $generalInfoCommand,
        /** @var SaveRegulationLocationCommand[] */
        public readonly array $locationCommands,
    ) {
        $generalInfoCommand->source = RegulationOrderRecordSourceEnum::BAC_IDF->value;
    }
}

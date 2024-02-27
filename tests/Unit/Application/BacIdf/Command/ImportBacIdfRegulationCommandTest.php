<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\BacIdf\Command;

use App\Application\BacIdf\Command\ImportBacIdfRegulationCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use PHPUnit\Framework\TestCase;

final class ImportBacIdfRegulationCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();

        new ImportBacIdfRegulationCommand($generalInfoCommand, []);

        $this->assertSame(RegulationOrderRecordSourceEnum::BAC_IDF->value, $generalInfoCommand->source);
    }
}

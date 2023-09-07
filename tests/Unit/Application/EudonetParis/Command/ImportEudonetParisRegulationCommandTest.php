<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\EudonetParis\Command;

use App\Application\EudonetParis\Command\ImportEudonetParisRegulationCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use PHPUnit\Framework\TestCase;

final class ImportEudonetParisRegulationCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();

        new ImportEudonetParisRegulationCommand($generalInfoCommand, []);

        $this->assertSame(RegulationOrderRecordSourceEnum::EUDONET_PARIS->value, $generalInfoCommand->source);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Integration\Litteralis\Command;

use App\Application\Integration\Litteralis\Command\ImportLitteralisRegulationCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use PHPUnit\Framework\TestCase;

final class ImportLitteralisRegulationCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();

        new ImportLitteralisRegulationCommand($generalInfoCommand, []);

        $this->assertSame(RegulationOrderRecordSourceEnum::LITTERALIS->value, $generalInfoCommand->source);
    }
}

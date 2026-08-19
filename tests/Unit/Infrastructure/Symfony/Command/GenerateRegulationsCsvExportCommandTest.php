<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Symfony\Command;

use App\Application\Regulation\RegulationExportCsvGeneratorInterface;
use App\Infrastructure\Symfony\Command\GenerateRegulationsCsvExportCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class GenerateRegulationsCsvExportCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $csvGenerator = $this->createMock(RegulationExportCsvGeneratorInterface::class);
        $csvGenerator->expects(self::once())->method('generate');

        $command = new GenerateRegulationsCsvExportCommand($csvGenerator);
        $commandTester = new CommandTester($command);

        $this->assertSame('app:regulations:generate-csv-export', $command->getName());

        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $this->assertStringContainsString('Regulations CSV export generated.', $commandTester->getDisplay());
    }
}

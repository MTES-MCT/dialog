<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command\Steps;

use App\Application\Regulation\Command\Steps\SaveRegulationStep2Command;
use App\Application\Regulation\Command\Steps\SaveRegulationStep2CommandHandler;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep2CommandHandlerTest extends TestCase
{
    public function testCreate(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('updateLastFilledStep')
            ->with(2);

        $handler = new SaveRegulationStep2CommandHandler();
        $command = new SaveRegulationStep2Command($regulationOrderRecord);

        $this->assertEmpty($handler($command));
    }
}

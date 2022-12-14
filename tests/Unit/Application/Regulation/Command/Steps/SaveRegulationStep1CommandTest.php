<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command\Steps;

use App\Application\Regulation\Command\Steps\SaveRegulationStep1Command;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep1CommandTest extends TestCase
{
    public function testWithoutRegulationOrderRecord(): void
    {
        $command = SaveRegulationStep1Command::create();

        $this->assertEmpty($command->description);
        $this->assertEmpty($command->issuingAuthority);
    }

    public function testWithRegulationOrderRecord(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');

        $regulationOrder
            ->expects(self::once())
            ->method('getIssuingAuthority')
            ->willReturn('Autorité');

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);

        $command = SaveRegulationStep1Command::create($regulationOrderRecord);

        $this->assertSame($command->description, 'Description');
        $this->assertSame($command->issuingAuthority, 'Autorité');
    }
}

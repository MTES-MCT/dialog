<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Steps;

use App\Application\Regulation\Command\Steps\SaveRegulationStep1Command;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep1CommandTest extends TestCase
{
    public function testWithoutRegulationOrderRecord(): void
    {
        $organization = $this->createMock(Organization::class);
        $organization->expects(self::once())
            ->method('getName')
            ->willReturn('DiaLog');

        $command = SaveRegulationStep1Command::create($organization);

        $this->assertSame('DiaLog', $command->issuingAuthority);
        $this->assertEmpty($command->description);
        $this->assertEmpty($command->startDate);
        $this->assertEmpty($command->endDate);
    }

    public function testWithRegulationOrderRecord(): void
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $start = new \DateTimeImmutable('2023-03-13');
        $end = new \DateTimeImmutable('2023-03-15');

        $regulationOrder
            ->expects(self::once())
            ->method('getIssuingAuthority')
            ->willReturn('Autorité');

        $regulationOrder
            ->expects(self::once())
            ->method('getDescription')
            ->willReturn('Description');

        $regulationOrder
            ->expects(self::once())
            ->method('getStartDate')
            ->willReturn($start);

        $regulationOrder
            ->expects(self::once())
            ->method('getEndDate')
            ->willReturn($end);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord
            ->expects(self::once())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);

        $command = SaveRegulationStep1Command::create($organization, $regulationOrderRecord);

        $this->assertSame($command->issuingAuthority, 'Autorité');
        $this->assertSame($command->description, 'Description');
        $this->assertSame($command->startDate, $start);
        $this->assertSame($command->endDate, $end);
    }
}

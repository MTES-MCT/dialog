<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Steps;

use App\Application\Regulation\Command\Steps\SaveRegulationStep2Command;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep2CommandTest extends TestCase
{
    public function testWithoutLocation(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $command = SaveRegulationStep2Command::create($regulationOrderRecord);

        $this->assertEmpty($command->location);
        $this->assertEmpty($command->address);
        $this->assertEmpty($command->fromHouseNumber);
        $this->assertEmpty($command->toHouseNumber);
    }

    public function testWithLocation(): void
    {
        $address = 'Route du Lac 44260 Savenay';
        $fromHouseNumber = '11';
        $toHouseNumber = '15';

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getAddress')
            ->willReturn($address);
        $location
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn($fromHouseNumber);
        $location
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn($toHouseNumber);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $command = SaveRegulationStep2Command::create($regulationOrderRecord, $location);

        $this->assertSame($command->location, $location);
        $this->assertSame($command->address, $address);
        $this->assertSame($command->fromHouseNumber, $fromHouseNumber);
        $this->assertSame($command->toHouseNumber, $toHouseNumber);
    }
}

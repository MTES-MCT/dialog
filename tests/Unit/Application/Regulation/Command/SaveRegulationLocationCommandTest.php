<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command;

use App\Application\Regulation\Command\Location\SaveRegulationLocationCommand;
use App\Domain\Regulation\Location;
use App\Domain\Regulation\RegulationOrderRecord;
use PHPUnit\Framework\TestCase;

final class SaveRegulationLocationCommandTest extends TestCase
{
    public function testWithoutLocation(): void
    {
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $command = SaveRegulationLocationCommand::create($regulationOrderRecord);

        $this->assertEmpty($command->location);
        $this->assertEmpty($command->cityCode);
        $this->assertEmpty($command->cityLabel);
        $this->assertEmpty($command->roadName);
        $this->assertEmpty($command->fromHouseNumber);
        $this->assertEmpty($command->toHouseNumber);
    }

    public function testWithLocation(): void
    {
        $cityCode = '44195';
        $cityLabel = 'Savenay';
        $roadName = 'Route du Lac';
        $fromHouseNumber = '11';
        $toHouseNumber = '15';

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getCityCode')
            ->willReturn($cityCode);
        $location
            ->expects(self::once())
            ->method('getCityLabel')
            ->willReturn($cityLabel);
        $location
            ->expects(self::once())
            ->method('getRoadName')
            ->willReturn($roadName);
        $location
            ->expects(self::once())
            ->method('getFromHouseNumber')
            ->willReturn($fromHouseNumber);
        $location
            ->expects(self::once())
            ->method('getToHouseNumber')
            ->willReturn($toHouseNumber);

        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $command = SaveRegulationLocationCommand::create($regulationOrderRecord, $location);

        $this->assertSame($command->location, $location);
        $this->assertSame($command->cityCode, $cityCode);
        $this->assertSame($command->cityLabel, $cityLabel);
        $this->assertSame($command->roadName, $roadName);
        $this->assertSame($command->fromHouseNumber, $fromHouseNumber);
        $this->assertSame($command->toHouseNumber, $toHouseNumber);
    }
}

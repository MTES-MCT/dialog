<?php

declare(strict_types=1);

namespace App\Tests\Domain\Regulation\Command\Steps;

use App\Application\Regulation\Command\Steps\SaveRegulationStep2Command;
use App\Domain\Condition\Location;
use App\Domain\Regulation\RegulationOrder;
use PHPUnit\Framework\TestCase;

final class SaveRegulationStep2CommandTest extends TestCase
{
    public function testWithoutLocation(): void
    {
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $command = SaveRegulationStep2Command::create($regulationOrder);

        $this->assertEmpty($command->location);
        $this->assertEmpty($command->postalCode);
        $this->assertEmpty($command->city);
        $this->assertEmpty($command->roadName);
        $this->assertEmpty($command->fromHouseNumber);
        $this->assertEmpty($command->toHouseNumber);
    }

    public function testWithLocation(): void
    {
        $postalCode = '44260';
        $city = 'Savenay';
        $roadName = 'Route du Lac';
        $fromHouseNumber = '11';
        $toHouseNumber = '15';

        $location = $this->createMock(Location::class);
        $location
            ->expects(self::once())
            ->method('getPostalCode')
            ->willReturn($postalCode);
        $location
            ->expects(self::once())
            ->method('getCity')
            ->willReturn($city);
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

        $regulationOrder = $this->createMock(RegulationOrder::class);
        $command = SaveRegulationStep2Command::create($regulationOrder, $location);

        $this->assertSame($command->location, $location);
        $this->assertSame($command->postalCode, $postalCode);
        $this->assertSame($command->city, $city);
        $this->assertSame($command->roadName, $roadName);
        $this->assertSame($command->fromHouseNumber, $fromHouseNumber);
        $this->assertSame($command->toHouseNumber, $toHouseNumber);
    }
}

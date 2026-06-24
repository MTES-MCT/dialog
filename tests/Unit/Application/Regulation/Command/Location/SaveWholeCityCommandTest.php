<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Application\Regulation\Query\Location\GetWholeCityGeometryQuery;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\WholeCityException;
use PHPUnit\Framework\TestCase;

final class SaveWholeCityCommandTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $command = new SaveWholeCityCommand();

        $this->assertNull($command->roadType);
        $this->assertNull($command->cityCode);
        $this->assertSame([], $command->exceptions);
        $this->assertInstanceOf(GetWholeCityGeometryQuery::class, $command->getGeometryQuery());
    }

    public function testHydrateFromLocation(): void
    {
        $exception = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(Location::class),
            roadType: RoadTypeEnum::LANE->value,
            label: 'Rue de Paris',
            geometry: '<geom>',
            data: ['roadBanId' => '59350_1234', 'roadName' => 'Rue de Paris'],
        );

        $location = $this->createMock(Location::class);
        $location->method('getRoadType')->willReturn(RoadTypeEnum::WHOLE_CITY->value);
        $location->method('getCityCode')->willReturn('59350');
        $location->method('getCityLabel')->willReturn('Lille');
        $location->method('getExceptions')->willReturn([$exception]);

        $command = new SaveWholeCityCommand($location);

        $this->assertSame(RoadTypeEnum::WHOLE_CITY->value, $command->roadType);
        $this->assertSame('59350', $command->cityCode);
        $this->assertSame('Lille', $command->cityLabel);
        $this->assertSame($location, $command->location);
        $this->assertCount(1, $command->exceptions);
        $this->assertSame(RoadTypeEnum::LANE->value, $command->exceptions[0]->roadType);
    }

    public function testCleanDropsIncompleteExceptions(): void
    {
        $command = new SaveWholeCityCommand();
        $command->exceptions = [
            $this->entireVoieException('59350_1234'),
            new SaveWholeCityExceptionCommand(), // incomplete (no roadType/roadBanId)
        ];

        $command->clean();

        $this->assertCount(1, $command->exceptions);
        $this->assertSame('59350_1234', $command->exceptions[0]->namedStreet->roadBanId);
    }

    public function testGetExcludedRoadBanIdsKeepsOnlyEntireVoies(): void
    {
        $section = $this->entireVoieException('59350_9999');
        $section->namedStreet->fromHouseNumber = '10'; // becomes a section

        $command = new SaveWholeCityCommand();
        $command->exceptions = [
            $this->entireVoieException('59350_1234'),
            $section,
        ];

        $this->assertSame(['59350_1234'], $command->getExcludedRoadBanIds());
    }

    private function entireVoieException(string $roadBanId): SaveWholeCityExceptionCommand
    {
        $exception = new SaveWholeCityExceptionCommand();
        $exception->roadType = RoadTypeEnum::LANE->value;
        $exception->namedStreet = new SaveNamedStreetCommand();
        $exception->namedStreet->roadBanId = $roadBanId;

        return $exception;
    }
}

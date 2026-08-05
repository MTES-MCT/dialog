<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Regulation\Command\Location\DeleteZoneCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveZoneCommand;
use App\Application\Regulation\Query\Location\GetZoneGeometryQuery;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\Zone;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class SaveZoneCommandTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $command = new SaveZoneCommand();

        $this->assertNull($command->roadType);
        $this->assertNull($command->label);
        $this->assertNull($command->geometry);
        $this->assertInstanceOf(GetZoneGeometryQuery::class, $command->getGeometryQuery());
    }

    public function testHydrateFromZone(): void
    {
        $location = $this->createMock(Location::class);
        $location->method('getRoadType')->willReturn(RoadTypeEnum::ZONE->value);

        $zone = $this->createMock(Zone::class);
        $zone->method('getLocation')->willReturn($location);
        $zone->method('getLabel')->willReturn('Centre-ville');
        $zone->method('getGeometry')->willReturn('<polygon>');

        $command = new SaveZoneCommand($zone);

        $this->assertSame(RoadTypeEnum::ZONE->value, $command->roadType);
        $this->assertSame('Centre-ville', $command->label);
        $this->assertSame('<polygon>', $command->geometry);
        $this->assertSame($location, $command->location);
    }

    public function testSaveLocationCommandHydratesZone(): void
    {
        $location = $this->makeZoneLocation();

        $command = new SaveLocationCommand($location);

        $this->assertInstanceOf(SaveZoneCommand::class, $command->zone);
        $this->assertSame('Centre-ville', $command->zone->label);
        $this->assertSame('<polygon>', $command->zone->geometry);
    }

    public function testSaveLocationCommandDeletesZoneWhenRoadTypeChanges(): void
    {
        $location = $this->makeZoneLocation();

        $command = new SaveLocationCommand($location);
        // L'utilisateur bascule vers un autre type : clean() vide la sous-commande zone.
        $command->roadType = RoadTypeEnum::LANE->value;
        $command->clean();

        $deleteCommand = $command->getRoadDeleteCommand();

        $this->assertInstanceOf(DeleteZoneCommand::class, $deleteCommand);
        $this->assertSame($location->getZone(), $deleteCommand->zone);
    }

    private function makeZoneLocation(): Location
    {
        $organization = $this->createMock(Organization::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);
        $regulationOrderRecord->method('getOrganization')->willReturn($organization);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrder->method('getRegulationOrderRecord')->willReturn($regulationOrderRecord);
        $measure = $this->createMock(Measure::class);
        $measure->method('getRegulationOrder')->willReturn($regulationOrder);

        $location = new Location(
            uuid: '0658c9b2-611b-7e35-8000-a29db4dbd687',
            measure: $measure,
            roadType: RoadTypeEnum::ZONE->value,
            geometry: '<sections>',
        );

        $location->setZone(new Zone(
            uuid: 'zone-uuid',
            location: $location,
            label: 'Centre-ville',
            geometry: '<polygon>',
        ));

        return $location;
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Repository;

use App\Domain\Condition\Period\DailyRange;
use App\Domain\Condition\Period\Period;
use App\Domain\Condition\Period\TimeSlot;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Measure;
use App\Infrastructure\Persistence\Doctrine\Repository\Regulation\MeasureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

final class MeasureRepositoryTest extends TestCase
{
    // Coverage only
    public function testDeleteDetachesRelatedEntities(): void
    {
        $namedStreet = $this->createMock(NamedStreet::class);
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $rawGeoJSON = $this->createMock(RawGeoJSON::class);

        $location = $this->createMock(Location::class);
        $location->method('getNamedStreet')->willReturn($namedStreet);
        $location->method('getNumberedRoad')->willReturn($numberedRoad);
        $location->method('getRawGeoJSON')->willReturn($rawGeoJSON);

        $dailyRange = $this->createMock(DailyRange::class);
        $timeSlot = $this->createMock(TimeSlot::class);

        $period = $this->createMock(Period::class);
        $period->method('getDailyRange')->willReturn($dailyRange);
        $period->method('getTimeSlots')->willReturn([$timeSlot]);

        $vehicleSet = $this->createMock(VehicleSet::class);

        $measure = $this->createMock(Measure::class);
        $measure->method('getLocations')->willReturn([$location]);
        $measure->method('getPeriods')->willReturn([$period]);
        $measure->method('getVehicleSet')->willReturn($vehicleSet);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects(self::once())
            ->method('remove')
            ->with($measure);
        $em
            ->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn(new ClassMetadata(Measure::class));

        $detached = [];
        $em
            ->expects(self::exactly(8))
            ->method('detach')
            ->willReturnCallback(function ($entity) use (&$detached): void {
                $detached[] = $entity;
            });

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Measure::class)
            ->willReturn($em);

        $repository = new MeasureRepository($registry);

        $repository->delete($measure);

        $this->assertSame(
            [$namedStreet, $numberedRoad, $rawGeoJSON, $location, $dailyRange, $timeSlot, $period, $vehicleSet],
            $detached,
        );
    }

    // Coverage only
    public function testDeleteWithoutRelatedEntities(): void
    {
        $location = $this->createMock(Location::class);
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn(null);
        $location->method('getRawGeoJSON')->willReturn(null);

        $period = $this->createMock(Period::class);
        $period->method('getDailyRange')->willReturn(null);
        $period->method('getTimeSlots')->willReturn([]);

        $measure = $this->createMock(Measure::class);
        $measure->method('getLocations')->willReturn([$location]);
        $measure->method('getPeriods')->willReturn([$period]);
        $measure->method('getVehicleSet')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em
            ->expects(self::once())
            ->method('remove')
            ->with($measure);
        $em
            ->expects(self::once())
            ->method('getClassMetadata')
            ->willReturn(new ClassMetadata(Measure::class));

        $detached = [];
        $em
            ->expects(self::exactly(2))
            ->method('detach')
            ->willReturnCallback(function ($entity) use (&$detached): void {
                $detached[] = $entity;
            });

        $registry = $this->createMock(ManagerRegistry::class);
        $registry
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Measure::class)
            ->willReturn($em);

        $repository = new MeasureRepository($registry);

        $repository->delete($measure);

        $this->assertSame([$location, $period], $detached);
    }
}

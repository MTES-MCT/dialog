<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Regulation\Location;

use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\WholeCityException;
use PHPUnit\Framework\TestCase;

final class WholeCityExceptionTest extends TestCase
{
    public function testGetters(): void
    {
        $location = $this->createMock(Location::class);

        $exception = new WholeCityException(
            uuid: 'uuid',
            location: $location,
            roadType: RoadTypeEnum::LANE->value,
            label: 'Rue de Paris',
            geometry: '<geom>',
            data: ['roadBanId' => '59350_1234'],
        );

        $this->assertSame('uuid', $exception->getUuid());
        $this->assertSame($location, $exception->getLocation());
        $this->assertSame(RoadTypeEnum::LANE->value, $exception->getRoadType());
        $this->assertSame('Rue de Paris', $exception->getLabel());
        $this->assertSame('<geom>', $exception->getGeometry());
        $this->assertSame(['roadBanId' => '59350_1234'], $exception->getData());
    }

    public function testGeometryDefaultsToNull(): void
    {
        $exception = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(Location::class),
            roadType: RoadTypeEnum::LANE->value,
            label: 'Rue de Paris',
        );

        $this->assertNull($exception->getGeometry());
        $this->assertSame([], $exception->getData());
    }

    public function testUpdate(): void
    {
        $exception = new WholeCityException(
            uuid: 'uuid',
            location: $this->createMock(Location::class),
            roadType: RoadTypeEnum::LANE->value,
            label: 'Rue de Paris',
            geometry: '<old>',
            data: ['roadBanId' => 'old'],
        );

        $exception->update(
            roadType: RoadTypeEnum::RAW_GEOJSON->value,
            label: 'Zone piétonne',
            geometry: '<new>',
            data: ['label' => 'Zone piétonne'],
        );

        $this->assertSame(RoadTypeEnum::RAW_GEOJSON->value, $exception->getRoadType());
        $this->assertSame('Zone piétonne', $exception->getLabel());
        $this->assertSame('<new>', $exception->getGeometry());
        $this->assertSame(['label' => 'Zone piétonne'], $exception->getData());
    }
}

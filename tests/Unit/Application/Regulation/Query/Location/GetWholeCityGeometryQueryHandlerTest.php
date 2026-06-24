<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Query\Location\GetWholeCityGeometryQuery;
use App\Application\Regulation\Query\Location\GetWholeCityGeometryQueryHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Location\Location;
use PHPUnit\Framework\TestCase;

final class GetWholeCityGeometryQueryHandlerTest extends TestCase
{
    public function testCompute(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59350')
            ->willReturn('<geometry>');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<geometry>', $handler(new GetWholeCityGeometryQuery($command)));
    }

    public function testReturnsProvidedGeometryWithoutComputing(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::never())
            ->method('computeCityGeometry');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<provided>', $handler(new GetWholeCityGeometryQuery($command, null, '<provided>')));
    }

    public function testReturnsCachedLocationGeometryWhenCityUnchanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::never())
            ->method('computeCityGeometry');

        $location = $this->createMock(Location::class);
        $location->method('getGeometry')->willReturn('<cached>');
        $location->method('getCityCode')->willReturn('59350');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<cached>', $handler(new GetWholeCityGeometryQuery($command, $location)));
    }

    public function testRecomputesWhenCityChanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59000')
            ->willReturn('<recomputed>');

        $location = $this->createMock(Location::class);
        $location->method('getGeometry')->willReturn('<cached>');
        $location->method('getCityCode')->willReturn('59350');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59000';

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<recomputed>', $handler(new GetWholeCityGeometryQuery($command, $location)));
    }
}

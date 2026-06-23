<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Application\Regulation\Query\Location\GetWholeCityGeometryQuery;
use App\Application\Regulation\Query\Location\GetWholeCityGeometryQueryHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\WholeCity;
use PHPUnit\Framework\TestCase;

final class GetWholeCityGeometryQueryHandlerTest extends TestCase
{
    public function testComputeWithoutExceptions(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59350', [])
            ->willReturn('<geometry>');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<geometry>', $handler(new GetWholeCityGeometryQuery($command)));
    }

    public function testComputeWithExceptions(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59350', ['ban1', 'ban2'])
            ->willReturn('<geometry>');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';

        foreach (['ban1', 'ban2'] as $banId) {
            $exception = new SaveWholeCityExceptionCommand();
            $exception->roadBanId = $banId;
            $command->exceptions[] = $exception;
        }

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

    public function testReturnsCachedLocationGeometryWhenUnchanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::never())
            ->method('computeCityGeometry');

        $location = $this->createMock(Location::class);
        $location
            ->method('getGeometry')
            ->willReturn('<cached>');

        $wholeCity = new WholeCity('uuid', $location, '59350', 'Lille');

        $command = new SaveWholeCityCommand($wholeCity);

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<cached>', $handler(new GetWholeCityGeometryQuery($command, $location)));
    }

    public function testRecomputesWhenExceptionsChanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59350', ['ban1'])
            ->willReturn('<recomputed>');

        $location = $this->createMock(Location::class);
        $location
            ->method('getGeometry')
            ->willReturn('<cached>');

        $wholeCity = new WholeCity('uuid', $location, '59350', 'Lille');

        $command = new SaveWholeCityCommand($wholeCity);
        $exception = new SaveWholeCityExceptionCommand();
        $exception->roadBanId = 'ban1';
        $command->exceptions = [$exception];

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder);

        $this->assertSame('<recomputed>', $handler(new GetWholeCityGeometryQuery($command, $location)));
    }
}

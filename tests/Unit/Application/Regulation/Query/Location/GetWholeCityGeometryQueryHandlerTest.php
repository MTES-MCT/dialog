<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Application\Regulation\Query\Location\GetWholeCityGeometryQuery;
use App\Application\Regulation\Query\Location\GetWholeCityGeometryQueryHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use PHPUnit\Framework\TestCase;

final class GetWholeCityGeometryQueryHandlerTest extends TestCase
{
    public function testComputeWithoutExceptions(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59350', [], [])
            ->willReturn('<geometry>');

        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus->expects(self::never())->method('handle');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder, $queryBus);

        $this->assertSame('<geometry>', $handler(new GetWholeCityGeometryQuery($command)));
    }

    public function testEntireVoieExceptionExcludedByBanId(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59350', ['59350_0010'], [])
            ->willReturn('<geometry>');

        // Entire voie => excluded by BAN id, no geometric subtraction needed.
        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus->expects(self::never())->method('handle');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';
        $command->exceptions[] = $this->entireVoieException('59350_0010');

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder, $queryBus);

        $this->assertSame('<geometry>', $handler(new GetWholeCityGeometryQuery($command)));
    }

    public function testSectionExceptionSubtractedGeometrically(): void
    {
        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus
            ->expects(self::once())
            ->method('handle')
            ->willReturn('<section-geometry>');

        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59350', [], ['<section-geometry>'])
            ->willReturn('<geometry>');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';
        $command->exceptions[] = $this->sectionException('59350_0010');

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder, $queryBus);

        $this->assertSame('<geometry>', $handler(new GetWholeCityGeometryQuery($command)));
    }

    public function testReturnsProvidedGeometryWithoutComputing(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder->expects(self::never())->method('computeCityGeometry');

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder, $this->createMock(QueryBusInterface::class));

        $this->assertSame('<provided>', $handler(new GetWholeCityGeometryQuery($command, null, '<provided>')));
    }

    public function testReturnsCachedLocationGeometryWhenUnchanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder->expects(self::never())->method('computeCityGeometry');
        $queryBus = $this->createMock(QueryBusInterface::class);
        $queryBus->expects(self::never())->method('handle');

        $location = $this->createMock(Location::class);
        $location->method('getGeometry')->willReturn('<cached>');
        $location->method('getCityCode')->willReturn('59350');
        $location->method('getExceptions')->willReturn([]);

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder, $queryBus);

        $this->assertSame('<cached>', $handler(new GetWholeCityGeometryQuery($command, $location)));
    }

    public function testRecomputesWhenCityChanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59000', [], [])
            ->willReturn('<recomputed>');

        $location = $this->createMock(Location::class);
        $location->method('getGeometry')->willReturn('<cached>');
        $location->method('getCityCode')->willReturn('59350');
        $location->method('getExceptions')->willReturn([]);

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59000';

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder, $this->createMock(QueryBusInterface::class));

        $this->assertSame('<recomputed>', $handler(new GetWholeCityGeometryQuery($command, $location)));
    }

    public function testRecomputesWhenExceptionsChanged(): void
    {
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $roadGeocoder
            ->expects(self::once())
            ->method('computeCityGeometry')
            ->with('59350', ['59350_0010'], [])
            ->willReturn('<recomputed>');

        // Persisted: no exceptions; command now has one => signatures differ => recompute.
        $location = $this->createMock(Location::class);
        $location->method('getGeometry')->willReturn('<cached>');
        $location->method('getCityCode')->willReturn('59350');
        $location->method('getExceptions')->willReturn([]);

        $command = new SaveWholeCityCommand();
        $command->cityCode = '59350';
        $command->exceptions[] = $this->entireVoieException('59350_0010');

        $handler = new GetWholeCityGeometryQueryHandler($roadGeocoder, $this->createMock(QueryBusInterface::class));

        $this->assertSame('<recomputed>', $handler(new GetWholeCityGeometryQuery($command, $location)));
    }

    private function entireVoieException(string $roadBanId): SaveWholeCityExceptionCommand
    {
        $exception = new SaveWholeCityExceptionCommand();
        $exception->roadType = RoadTypeEnum::LANE->value;
        $exception->namedStreet = new SaveNamedStreetCommand();
        $exception->namedStreet->roadType = RoadTypeEnum::LANE->value;
        $exception->namedStreet->roadBanId = $roadBanId;

        return $exception;
    }

    private function sectionException(string $roadBanId): SaveWholeCityExceptionCommand
    {
        $exception = $this->entireVoieException($roadBanId);
        // A "from" point makes it a section (not an entire street).
        $exception->namedStreet->fromHouseNumber = '10';

        return $exception;
    }
}

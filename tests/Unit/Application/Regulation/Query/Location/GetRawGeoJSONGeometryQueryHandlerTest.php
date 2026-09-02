<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query\Location;

use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\SaveNamedStreetCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Location\SaveWholeCityExceptionCommand;
use App\Application\Regulation\Query\Location\GetRawGeoJSONGeometryQuery;
use App\Application\Regulation\Query\Location\GetRawGeoJSONGeometryQueryHandler;
use App\Application\RoadGeocoderInterface;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Location\WholeCityException;
use PHPUnit\Framework\TestCase;

final class GetRawGeoJSONGeometryQueryHandlerTest extends TestCase
{
    private $roadGeocoder;
    private $queryBus;

    public function setUp(): void
    {
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->queryBus = $this->createMock(QueryBusInterface::class);
    }

    private function makeLaneException(string $cityCode, string $roadBanId, string $roadName): SaveWholeCityExceptionCommand
    {
        $exception = new SaveWholeCityExceptionCommand();
        $exception->roadType = RoadTypeEnum::LANE->value;
        $exception->namedStreet = new SaveNamedStreetCommand();
        $exception->namedStreet->roadType = RoadTypeEnum::LANE->value;
        $exception->namedStreet->cityCode = $cityCode;
        $exception->namedStreet->roadBanId = $roadBanId;
        $exception->namedStreet->roadName = $roadName;
        $exception->namedStreet->setIsEntireStreet(true);

        return $exception;
    }

    public function testReturnsGeometryWithoutExceptions(): void
    {
        $this->roadGeocoder->expects(self::never())->method('subtractGeometries');
        $this->queryBus->expects(self::never())->method('handle');

        $command = new SaveRawGeoJSONCommand();
        $command->geometry = '<geometry>';

        $handler = new GetRawGeoJSONGeometryQueryHandler($this->roadGeocoder, $this->queryBus);

        $this->assertSame('<geometry>', $handler(new GetRawGeoJSONGeometryQuery($command)));
    }

    public function testSubtractsExceptions(): void
    {
        $exception = $this->makeLaneException('93070', '93070_3185', 'Rue Eugène Berthoud');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->with($exception->namedStreet->getGeometryQuery())
            ->willReturn('<exception-geometry>');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('subtractGeometries')
            ->with('<geometry>', ['<exception-geometry>'])
            ->willReturn('<geometry-minus-exceptions>');

        $command = new SaveRawGeoJSONCommand();
        $command->geometry = '<geometry>';
        $command->exceptions = [$exception];

        $handler = new GetRawGeoJSONGeometryQueryHandler($this->roadGeocoder, $this->queryBus);

        $this->assertSame('<geometry-minus-exceptions>', $handler(new GetRawGeoJSONGeometryQuery($command)));
    }

    public function testReturnsCachedLocationGeometryWhenUnchanged(): void
    {
        $this->roadGeocoder->expects(self::never())->method('subtractGeometries');
        $this->queryBus->expects(self::never())->method('handle');

        $exception = $this->makeLaneException('93070', '93070_3185', 'Rue Eugène Berthoud');

        $rawGeoJSON = $this->createMock(RawGeoJSON::class);
        $rawGeoJSON->method('getGeometry')->willReturn('<geometry>');

        $location = $this->createMock(Location::class);
        $location->method('getRawGeoJSON')->willReturn($rawGeoJSON);
        $location->method('getGeometry')->willReturn('<geometry-minus-exceptions>');
        $location->method('getExceptions')->willReturn([
            new WholeCityException(
                uuid: '0662d842-0d55-7ff1-8000-3ba613adca43',
                location: $location,
                roadType: RoadTypeEnum::LANE->value,
                label: 'Rue Eugène Berthoud',
                geometry: '<exception-geometry>',
                data: $exception->toData(),
            ),
        ]);

        $command = new SaveRawGeoJSONCommand();
        $command->geometry = '<geometry>';
        $command->exceptions = [$exception];

        $handler = new GetRawGeoJSONGeometryQueryHandler($this->roadGeocoder, $this->queryBus);

        $this->assertSame('<geometry-minus-exceptions>', $handler(new GetRawGeoJSONGeometryQuery($command, $location)));
    }

    public function testRecomputesWhenDrawnGeometryChanged(): void
    {
        $exception = $this->makeLaneException('93070', '93070_3185', 'Rue Eugène Berthoud');

        $this->queryBus
            ->expects(self::once())
            ->method('handle')
            ->willReturn('<exception-geometry>');

        $this->roadGeocoder
            ->expects(self::once())
            ->method('subtractGeometries')
            ->with('<geometry-new>', ['<exception-geometry>'])
            ->willReturn('<geometry-new-minus-exceptions>');

        $rawGeoJSON = $this->createMock(RawGeoJSON::class);
        $rawGeoJSON->method('getGeometry')->willReturn('<geometry-old>');

        $location = $this->createMock(Location::class);
        $location->method('getRawGeoJSON')->willReturn($rawGeoJSON);
        $location->method('getGeometry')->willReturn('<geometry-minus-exceptions>');
        $location->method('getExceptions')->willReturn([]);

        $command = new SaveRawGeoJSONCommand();
        $command->geometry = '<geometry-new>';
        $command->exceptions = [$exception];

        $handler = new GetRawGeoJSONGeometryQueryHandler($this->roadGeocoder, $this->queryBus);

        $this->assertSame('<geometry-new-minus-exceptions>', $handler(new GetRawGeoJSONGeometryQuery($command, $location)));
    }
}

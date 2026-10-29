<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Command\Location;

use App\Application\Exception\GeocodingFailureException;
use App\Application\QueryBusInterface;
use App\Application\Regulation\Command\Location\RecomputeOneWayLocationGeometriesCommand;
use App\Application\Regulation\Command\Location\RecomputeOneWayLocationGeometriesCommandHandler;
use App\Application\Regulation\Command\Location\RecomputeOneWayLocationGeometriesCommandResult;
use App\Application\Regulation\Query\Location\GetNamedStreetGeometryQuery;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\LocationRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\TestCase;

final class RecomputeOneWayLocationGeometriesCommandHandlerTest extends TestCase
{
    private $queryBus;
    private $locationRepository;

    public function setUp(): void
    {
        $this->queryBus = $this->createMock(QueryBusInterface::class);
        $this->locationRepository = $this->createMock(LocationRepositoryInterface::class);
    }

    public function testNoLocations(): void
    {
        $this->locationRepository
            ->expects(self::once())
            ->method('findAllOneWaySections')
            ->willReturn([]);

        $this->queryBus
            ->expects(self::never())
            ->method('handle');

        $handler = new RecomputeOneWayLocationGeometriesCommandHandler($this->queryBus, $this->locationRepository);

        $this->assertEquals(
            new RecomputeOneWayLocationGeometriesCommandResult(
                numLocations: 0,
                updatedLocationUuids: [],
                unchangedLocationUuids: [],
                exceptions: [],
            ),
            $handler(new RecomputeOneWayLocationGeometriesCommand()),
        );
    }

    private function provideDryRun(): array
    {
        return [
            'apply' => ['dryRun' => false],
            'dryRun' => ['dryRun' => true],
        ];
    }

    /**
     * @dataProvider provideDryRun
     */
    public function testRecompute(bool $dryRun): void
    {
        $organization = $this->createMock(Organization::class);
        $measure = $this->createMock(Measure::class);
        $regulationOrder = $this->createMock(RegulationOrder::class);
        $regulationOrderRecord = $this->createMock(RegulationOrderRecord::class);

        $measure->expects(self::any())
            ->method('getRegulationOrder')
            ->willReturn($regulationOrder);
        $regulationOrder->expects(self::any())
            ->method('getRegulationOrderRecord')
            ->willReturn($regulationOrderRecord);
        $regulationOrderRecord->expects(self::any())
            ->method('getOrganization')
            ->willReturn($organization);

        $uuid1 = 'fb704ea8-6782-45bb-b8ac-75f7444d4760'; // Géométrie modifiée
        $uuid2 = '689d9fd4-1cf9-4a1a-b50f-fc80916d8df8'; // Géométrie déjà orientée
        $uuid3 = '80018f94-5929-4ca0-8ad9-ca0c97a6c46a'; // Échec de géocodage

        $locations = [];

        foreach ([$uuid1, $uuid2, $uuid3] as $uuid) {
            $location = $this->createMock(Location::class);
            $location->expects(self::any())
                ->method('getMeasure')
                ->willReturn($measure);
            $location->expects(self::any())
                ->method('getRoadType')
                ->willReturn(RoadTypeEnum::LANE->value);
            $location->expects(self::any())
                ->method('getNamedStreet')
                ->willReturn($this->createMock(NamedStreet::class));
            $location->expects(self::any())
                ->method('getUuid')
                ->willReturn($uuid);
            $location->expects(self::any())
                ->method('getGeometry')
                ->willReturn('oldGeometry');

            $locations[] = $location;
        }

        [$location1, $location2, $location3] = $locations;

        // Seule la géométrie de l'emprise 1 change, et uniquement hors « dry run »
        $location1
            ->expects($dryRun ? self::never() : self::once())
            ->method('update')
            ->with(RoadTypeEnum::LANE->value, 'newGeometry');
        $location2
            ->expects(self::never())
            ->method('update');
        $location3
            ->expects(self::never())
            ->method('update');

        $this->locationRepository
            ->expects(self::once())
            ->method('findAllOneWaySections')
            ->willReturn($locations);

        $matcher = self::exactly(3);
        $this->queryBus
            ->expects($matcher)
            ->method('handle')
            ->with(self::isInstanceOf(GetNamedStreetGeometryQuery::class))
            ->willReturnCallback(
                fn ($query) => match ($matcher->getInvocationCount()) {
                    1 => 'newGeometry',
                    2 => 'oldGeometry',
                    3 => throw new GeocodingFailureException('oops 3'),
                },
            );

        $handler = new RecomputeOneWayLocationGeometriesCommandHandler($this->queryBus, $this->locationRepository);

        $this->assertEquals(
            new RecomputeOneWayLocationGeometriesCommandResult(
                numLocations: 3,
                updatedLocationUuids: [$uuid1],
                unchangedLocationUuids: [$uuid2],
                exceptions: [$uuid3 => new GeocodingFailureException('oops 3')],
            ),
            $handler(new RecomputeOneWayLocationGeometriesCommand(dryRun: $dryRun)),
        );
    }
}

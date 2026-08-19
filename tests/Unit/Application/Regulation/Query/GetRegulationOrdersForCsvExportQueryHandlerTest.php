<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Regulation\Query;

use App\Application\DateUtilsInterface;
use App\Application\Regulation\Query\GetRegulationOrdersForCsvExportQuery;
use App\Application\Regulation\Query\GetRegulationOrdersForCsvExportQueryHandler;
use App\Application\Regulation\View\RegulationCsvRowView;
use App\Application\StorageInterface;
use App\Domain\Condition\VehicleSet;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Location\Location;
use App\Domain\Regulation\Location\NamedStreet;
use App\Domain\Regulation\Location\NumberedRoad;
use App\Domain\Regulation\Location\RawGeoJSON;
use App\Domain\Regulation\Location\Zone;
use App\Domain\Regulation\Measure;
use App\Domain\Regulation\RegulationOrder;
use App\Domain\Regulation\RegulationOrderRecord;
use App\Domain\Regulation\Repository\RegulationOrderRecordRepositoryInterface;
use App\Domain\Regulation\Repository\StorageRegulationOrderRepositoryInterface;
use App\Domain\User\Organization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetRegulationOrdersForCsvExportQueryHandlerTest extends TestCase
{
    private RegulationOrderRecordRepositoryInterface&MockObject $regulationOrderRecordRepository;
    private StorageRegulationOrderRepositoryInterface&MockObject $storageRegulationOrderRepository;
    private StorageInterface&MockObject $storage;
    private DateUtilsInterface&MockObject $dateUtils;
    private GetRegulationOrdersForCsvExportQueryHandler $handler;

    protected function setUp(): void
    {
        $this->regulationOrderRecordRepository = $this->createMock(RegulationOrderRecordRepositoryInterface::class);
        $this->storageRegulationOrderRepository = $this->createMock(StorageRegulationOrderRepositoryInterface::class);
        $this->storage = $this->createMock(StorageInterface::class);
        $this->dateUtils = $this->createMock(DateUtilsInterface::class);
        $this->dateUtils->method('getNow')->willReturn(new \DateTimeImmutable('2025-01-01'));
        $this->handler = new GetRegulationOrdersForCsvExportQueryHandler(
            $this->regulationOrderRecordRepository,
            $this->storageRegulationOrderRepository,
            $this->storage,
            $this->dateUtils,
        );
    }

    /**
     * @param Location[] $locations
     */
    private function makeRecord(
        string $uuid,
        array $locations = [],
        ?array $restrictedTypes = null,
        ?string $organizationName = 'Org',
    ): RegulationOrderRecord {
        $regulationOrder = new RegulationOrder($uuid . '-ro', 'F/1', 'temporaryRegulation', 'Title');
        $measure = new Measure($uuid . '-m', $regulationOrder, 'noEntry', new \DateTimeImmutable());

        if ($restrictedTypes !== null) {
            $measure->setVehicleSet(new VehicleSet($uuid . '-v', $measure, $restrictedTypes));
        }

        foreach ($locations as $location) {
            $measure->addLocation($location);
        }

        $regulationOrder->addMeasure($measure);

        $organization = $this->createMock(Organization::class);
        $organization->method('getUuid')->willReturn('org-uuid');
        $organization->method('getName')->willReturn($organizationName);

        return new RegulationOrderRecord($uuid, 'dialog', 'published', $regulationOrder, new \DateTimeImmutable(), $organization);
    }

    private function makeLocation(string $roadType = RoadTypeEnum::LANE->value): Location&MockObject
    {
        $location = $this->createMock(Location::class);
        $location->method('getUuid')->willReturn('loc-uuid');
        $location->method('getRoadType')->willReturn($roadType);

        return $location;
    }

    public function testReturnsEmptyArrayWhenNoUuids(): void
    {
        $this->regulationOrderRecordRepository->method('findUuidsForApi')->willReturn([]);
        $this->regulationOrderRecordRepository->expects(self::never())->method('iterateRegulationOrdersForApiByUuids');

        $this->assertSame([], $this->handler->__invoke(new GetRegulationOrdersForCsvExportQuery()));
    }

    public function testBuildsRowWithNamedStreetLabelAndPdfUrl(): void
    {
        $namedStreet = $this->createMock(NamedStreet::class);
        $namedStreet->method('getRoadName')->willReturn('Rue de la Paix');
        $namedStreet->method('getCityLabel')->willReturn('Paris');

        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn($namedStreet);

        $record = $this->makeRecord('uuid-1', [$location]);

        $this->regulationOrderRecordRepository->method('findUuidsForApi')->willReturn(['uuid-1']);
        $this->regulationOrderRecordRepository->method('getOverallDatesByRegulationUuids')->willReturn([
            'uuid-1' => [
                'overallStartDate' => new \DateTimeImmutable('2025-01-01'),
                'overallEndDate' => new \DateTimeImmutable('2025-02-01'),
            ],
        ]);
        $this->regulationOrderRecordRepository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$record]);
        $this->storageRegulationOrderRepository->method('findPdfInfoByRegulationOrderUuids')->willReturn([
            'uuid-1-ro' => ['path' => null, 'url' => 'https://example.org/doc.pdf'],
        ]);

        $rows = $this->handler->__invoke(new GetRegulationOrdersForCsvExportQuery());

        $this->assertCount(1, $rows);
        /** @var RegulationCsvRowView $row */
        $row = $rows[0];
        $this->assertInstanceOf(RegulationCsvRowView::class, $row);
        $this->assertSame('uuid-1', $row->regulationOrderUuid);
        $this->assertSame('Title', $row->title);
        $this->assertSame('Org', $row->organizationName);
        $this->assertSame('https://example.org/doc.pdf', $row->linkPdf);
        $this->assertSame('Rue de la Paix, Paris', $row->locationLabel);
        $this->assertEquals(new \DateTimeImmutable('2025-01-01'), $row->startDate);
        $this->assertEquals(new \DateTimeImmutable('2025-02-01'), $row->endDate);
    }

    public function testResolvesPdfLinkFromStoragePathWhenNoUrl(): void
    {
        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn(null);
        $location->method('getZone')->willReturn(null);
        $location->method('getRawGeoJSON')->willReturn(null);

        $record = $this->makeRecord('uuid-1', [$location]);

        $this->regulationOrderRecordRepository->method('findUuidsForApi')->willReturn(['uuid-1']);
        $this->regulationOrderRecordRepository->method('getOverallDatesByRegulationUuids')->willReturn([]);
        $this->regulationOrderRecordRepository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$record]);
        $this->storageRegulationOrderRepository->method('findPdfInfoByRegulationOrderUuids')->willReturn([
            'uuid-1-ro' => ['path' => 'pdf/doc.pdf', 'url' => null],
        ]);
        $this->storage->method('getUrl')->with('pdf/doc.pdf')->willReturn('https://storage.example.org/pdf/doc.pdf');

        $rows = $this->handler->__invoke(new GetRegulationOrdersForCsvExportQuery());

        $this->assertSame('https://storage.example.org/pdf/doc.pdf', $rows[0]->linkPdf);
        $this->assertNull($rows[0]->startDate);
        $this->assertNull($rows[0]->endDate);
        $this->assertSame('', $rows[0]->locationLabel);
    }

    public function testResolvesEmptyPdfLinkWhenPdfInfoMissing(): void
    {
        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn(null);
        $location->method('getZone')->willReturn(null);
        $location->method('getRawGeoJSON')->willReturn(null);

        $record = $this->makeRecord('uuid-1', [$location], organizationName: '');

        $this->regulationOrderRecordRepository->method('findUuidsForApi')->willReturn(['uuid-1']);
        $this->regulationOrderRecordRepository->method('getOverallDatesByRegulationUuids')->willReturn([]);
        $this->regulationOrderRecordRepository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$record]);
        $this->storageRegulationOrderRepository->method('findPdfInfoByRegulationOrderUuids')->willReturn([]);

        $rows = $this->handler->__invoke(new GetRegulationOrdersForCsvExportQuery());

        $this->assertSame('', $rows[0]->linkPdf);
        $this->assertSame('', $rows[0]->organizationName);
    }

    public function testResolvesEmptyPdfLinkWhenPathAndUrlAreEmpty(): void
    {
        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn(null);
        $location->method('getZone')->willReturn(null);
        $location->method('getRawGeoJSON')->willReturn(null);

        $record = $this->makeRecord('uuid-1', [$location]);

        $this->regulationOrderRecordRepository->method('findUuidsForApi')->willReturn(['uuid-1']);
        $this->regulationOrderRecordRepository->method('getOverallDatesByRegulationUuids')->willReturn([]);
        $this->regulationOrderRecordRepository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$record]);
        $this->storageRegulationOrderRepository->method('findPdfInfoByRegulationOrderUuids')->willReturn([
            'uuid-1-ro' => ['path' => null, 'url' => null],
        ]);

        $rows = $this->handler->__invoke(new GetRegulationOrdersForCsvExportQuery());

        $this->assertSame('', $rows[0]->linkPdf);
    }

    public function testExcludesHeavyGoodsVehicleWhenFilterIsFalse(): void
    {
        $withHgv = $this->makeRecord('uuid-hgv', [$this->makeLocation()], [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value]);

        $namedStreet = $this->createMock(NamedStreet::class);
        $namedStreet->method('getRoadName')->willReturn('Rue A');
        $namedStreet->method('getCityLabel')->willReturn('Ville');
        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn($namedStreet);
        $withoutHgv = $this->makeRecord('uuid-other', [$location], [VehicleTypeEnum::BICYCLE->value]);

        $this->regulationOrderRecordRepository->method('findUuidsForApi')->willReturn(['uuid-hgv', 'uuid-other']);
        $this->regulationOrderRecordRepository->method('getOverallDatesByRegulationUuids')->willReturn([]);
        $this->regulationOrderRecordRepository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$withHgv, $withoutHgv]);
        $this->storageRegulationOrderRepository->method('findPdfInfoByRegulationOrderUuids')->willReturn([]);

        $rows = $this->handler->__invoke(new GetRegulationOrdersForCsvExportQuery(includeHeavyGoodsVehicle: false));

        $this->assertCount(1, $rows);
        $this->assertSame('uuid-other', $rows[0]->regulationOrderUuid);
    }

    public function testIncludesHeavyGoodsVehicleByDefault(): void
    {
        $withHgv = $this->makeRecord('uuid-hgv', [$this->makeLocationWithoutSubEntities()], [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value]);

        $this->regulationOrderRecordRepository->method('findUuidsForApi')->willReturn(['uuid-hgv']);
        $this->regulationOrderRecordRepository->method('getOverallDatesByRegulationUuids')->willReturn([]);
        $this->regulationOrderRecordRepository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$withHgv]);
        $this->storageRegulationOrderRepository->method('findPdfInfoByRegulationOrderUuids')->willReturn([]);

        $rows = $this->handler->__invoke(new GetRegulationOrdersForCsvExportQuery());

        $this->assertCount(1, $rows);
    }

    public function testBuildsLabelFromNumberedRoadWithAdministrator(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad->method('getRoadNumber')->willReturn('D920');
        $numberedRoad->method('getAdministrator')->willReturn('Département');

        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn($numberedRoad);

        $rows = $this->handleSingleLocation($location);

        $this->assertSame('D920 (Département)', $rows[0]->locationLabel);
    }

    public function testBuildsLabelFromNumberedRoadWithoutAdministrator(): void
    {
        $numberedRoad = $this->createMock(NumberedRoad::class);
        $numberedRoad->method('getRoadNumber')->willReturn('D920');
        $numberedRoad->method('getAdministrator')->willReturn(null);

        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn($numberedRoad);

        $rows = $this->handleSingleLocation($location);

        $this->assertSame('D920', $rows[0]->locationLabel);
    }

    public function testBuildsLabelFromZone(): void
    {
        $zone = $this->createMock(Zone::class);
        $zone->method('getLabel')->willReturn('Centre-ville');

        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn(null);
        $location->method('getZone')->willReturn($zone);

        $rows = $this->handleSingleLocation($location);

        $this->assertSame('Centre-ville', $rows[0]->locationLabel);
    }

    public function testBuildsLabelFromRawGeoJSON(): void
    {
        $rawGeoJSON = $this->createMock(RawGeoJSON::class);
        $rawGeoJSON->method('getLabel')->willReturn('Tracé personnalisé');

        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn(null);
        $location->method('getZone')->willReturn(null);
        $location->method('getRawGeoJSON')->willReturn($rawGeoJSON);

        $rows = $this->handleSingleLocation($location);

        $this->assertSame('Tracé personnalisé', $rows[0]->locationLabel);
    }

    public function testBuildsLabelFromWholeCity(): void
    {
        $location = $this->createMock(Location::class);
        $location->method('getUuid')->willReturn('loc-uuid');
        $location->method('getRoadType')->willReturn(RoadTypeEnum::WHOLE_CITY->value);
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn(null);
        $location->method('getZone')->willReturn(null);
        $location->method('getRawGeoJSON')->willReturn(null);
        $location->method('getCityLabel')->willReturn('Lyon');

        $rows = $this->handleSingleLocation($location);

        $this->assertSame('Lyon', $rows[0]->locationLabel);
    }

    private function makeLocationWithoutSubEntities(): Location&MockObject
    {
        $location = $this->makeLocation();
        $location->method('getNamedStreet')->willReturn(null);
        $location->method('getNumberedRoad')->willReturn(null);
        $location->method('getZone')->willReturn(null);
        $location->method('getRawGeoJSON')->willReturn(null);

        return $location;
    }

    /**
     * @return RegulationCsvRowView[]
     */
    private function handleSingleLocation(Location $location): array
    {
        $record = $this->makeRecord('uuid-1', [$location]);

        $this->regulationOrderRecordRepository->method('findUuidsForApi')->willReturn(['uuid-1']);
        $this->regulationOrderRecordRepository->method('getOverallDatesByRegulationUuids')->willReturn([]);
        $this->regulationOrderRecordRepository->method('iterateRegulationOrdersForApiByUuids')->willReturn([$record]);
        $this->storageRegulationOrderRepository->method('findPdfInfoByRegulationOrderUuids')->willReturn([]);

        return $this->handler->__invoke(new GetRegulationOrdersForCsvExportQuery());
    }
}

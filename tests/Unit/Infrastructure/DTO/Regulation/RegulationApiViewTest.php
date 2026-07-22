<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\DTO\Regulation;

use App\Application\Regulation\View\DailyRangeView;
use App\Application\Regulation\View\GeneralInfoView;
use App\Application\Regulation\View\Measure\LocationView;
use App\Application\Regulation\View\Measure\MeasureView;
use App\Application\Regulation\View\Measure\NamedStreetView;
use App\Application\Regulation\View\Measure\NumberedRoadView;
use App\Application\Regulation\View\Measure\RawGeoJSONView;
use App\Application\Regulation\View\Measure\StorageAreaView;
use App\Application\Regulation\View\PeriodView;
use App\Application\Regulation\View\RegulationOrderForApiView;
use App\Application\Regulation\View\TimeSlotView;
use App\Application\Regulation\View\VehicleSetView;
use App\Domain\Regulation\Enum\RegulationOrderRecordSourceEnum;
use App\Infrastructure\DTO\Regulation\DailyRangeApiView;
use App\Infrastructure\DTO\Regulation\LocationApiView;
use App\Infrastructure\DTO\Regulation\MeasureApiView;
use App\Infrastructure\DTO\Regulation\NamedStreetApiView;
use App\Infrastructure\DTO\Regulation\NumberedRoadApiView;
use App\Infrastructure\DTO\Regulation\OrganizationApiView;
use App\Infrastructure\DTO\Regulation\PeriodApiView;
use App\Infrastructure\DTO\Regulation\RawGeoJSONApiView;
use App\Infrastructure\DTO\Regulation\RegulationApiView;
use App\Infrastructure\DTO\Regulation\StorageAreaApiView;
use App\Infrastructure\DTO\Regulation\TimeSlotApiView;
use App\Infrastructure\DTO\Regulation\VehicleSetApiView;
use PHPUnit\Framework\TestCase;

final class RegulationApiViewTest extends TestCase
{
    public function testFromViewsMapsGeneralInfoAndOrganization(): void
    {
        $generalInfo = $this->createGeneralInfo();

        $view = RegulationApiView::fromViews($generalInfo, []);

        $this->assertSame('F2025/001', $view->identifier);
        $this->assertSame('draft', $view->status);
        $this->assertSame('temporaryRegulation', $view->category);
        $this->assertSame('roadMaintenance', $view->subject);
        $this->assertNull($view->otherCategoryText);
        $this->assertSame('Travaux de voirie rue Exemple', $view->title);
        $this->assertEquals(new \DateTimeImmutable('2025-10-09T08:00:00+00:00'), $view->startDate);
        $this->assertEquals(new \DateTimeImmutable('2025-10-15T18:00:00+00:00'), $view->endDate);

        $this->assertInstanceOf(OrganizationApiView::class, $view->organization);
        $this->assertSame('org-uuid', $view->organization->uuid);
        $this->assertSame('Ma collectivité', $view->organization->name);

        $this->assertSame([], $view->measures);
    }

    public function testFromApiViewMapsRegulationOrderForApiView(): void
    {
        $measure = new MeasureView(
            uuid: 'measure-uuid',
            type: 'noEntry',
            periods: [],
            vehicleSet: null,
            maxSpeed: null,
            locations: [],
        );

        $apiView = new RegulationOrderForApiView(
            identifier: 'F2025/002',
            status: 'published',
            category: 'temporaryRegulation',
            subject: 'roadMaintenance',
            otherCategoryText: null,
            title: 'Arrêté temporaire',
            startDate: new \DateTimeImmutable('2025-10-09T08:00:00+00:00'),
            endDate: new \DateTimeImmutable('2025-10-15T18:00:00+00:00'),
            organizationUuid: 'org-uuid',
            organizationName: 'Ma collectivité',
            measures: [$measure],
        );

        $view = RegulationApiView::fromApiView($apiView);

        $this->assertSame('F2025/002', $view->identifier);
        $this->assertSame('published', $view->status);
        $this->assertSame('temporaryRegulation', $view->category);
        $this->assertSame('roadMaintenance', $view->subject);
        $this->assertSame('Arrêté temporaire', $view->title);
        $this->assertEquals(new \DateTimeImmutable('2025-10-09T08:00:00+00:00'), $view->startDate);
        $this->assertInstanceOf(OrganizationApiView::class, $view->organization);
        $this->assertSame('org-uuid', $view->organization->uuid);
        $this->assertSame('Ma collectivité', $view->organization->name);
        $this->assertCount(1, $view->measures);
        $this->assertInstanceOf(MeasureApiView::class, $view->measures[0]);
        $this->assertSame('measure-uuid', $view->measures[0]->uuid);
    }

    public function testFromViewsMapsMeasureTree(): void
    {
        $measure = new MeasureView(
            uuid: 'measure-uuid',
            type: 'speedLimitation',
            periods: [
                new PeriodView(
                    'everyDay',
                    new \DateTimeImmutable('2025-10-09T08:00:00+00:00'),
                    new \DateTimeImmutable('2025-10-15T18:00:00+00:00'),
                    new DailyRangeView(['monday', 'tuesday']),
                    [
                        new TimeSlotView(
                            new \DateTimeImmutable('2025-10-09T08:00:00+00:00'),
                            new \DateTimeImmutable('2025-10-09T12:00:00+00:00'),
                        ),
                    ],
                ),
            ],
            vehicleSet: new VehicleSetView(
                restrictedTypes: [['name' => 'heavyGoodsVehicle']],
                exemptedTypes: [['name' => 'emergencyServices']],
                maxCharacteristics: [['name' => 'weight', 'value' => 3.5]],
            ),
            maxSpeed: 30,
            locations: [
                new LocationView(
                    uuid: 'loc-1',
                    roadType: 'lane',
                    namedStreet: new NamedStreetView('Paris', 'Rue Exemple', '1', null, '10', null),
                ),
                new LocationView(
                    uuid: 'loc-2',
                    roadType: 'departmentalRoad',
                    numberedRoad: new NumberedRoadView('SSD', 'D1', '1', 0, 'U', '5', 100, 'U'),
                    storageArea: new StorageAreaView('Aire'),
                ),
                new LocationView(
                    uuid: 'loc-3',
                    roadType: 'rawGeoJSON',
                    rawGeoJSON: new RawGeoJSONView('Zone'),
                ),
            ],
        );

        $view = RegulationApiView::fromViews($this->createGeneralInfo(), [$measure]);

        $this->assertCount(1, $view->measures);
        $measureView = $view->measures[0];
        $this->assertInstanceOf(MeasureApiView::class, $measureView);
        $this->assertSame('measure-uuid', $measureView->uuid);
        $this->assertSame('speedLimitation', $measureView->type);
        $this->assertSame(30, $measureView->maxSpeed);

        $this->assertInstanceOf(VehicleSetApiView::class, $measureView->vehicleSet);
        $this->assertSame([['name' => 'heavyGoodsVehicle']], $measureView->vehicleSet->restrictedTypes);

        $this->assertCount(1, $measureView->periods);
        $period = $measureView->periods[0];
        $this->assertInstanceOf(PeriodApiView::class, $period);
        $this->assertSame('everyDay', $period->recurrenceType);
        $this->assertInstanceOf(DailyRangeApiView::class, $period->dailyRange);
        $this->assertSame(['monday', 'tuesday'], $period->dailyRange->dayRanges);
        $this->assertCount(1, $period->timeSlots);
        $this->assertInstanceOf(TimeSlotApiView::class, $period->timeSlots[0]);

        $this->assertCount(3, $measureView->locations);
        $this->assertInstanceOf(LocationApiView::class, $measureView->locations[0]);
        $this->assertInstanceOf(NamedStreetApiView::class, $measureView->locations[0]->namedStreet);
        $this->assertNull($measureView->locations[0]->numberedRoad);

        $this->assertInstanceOf(NumberedRoadApiView::class, $measureView->locations[1]->numberedRoad);
        $this->assertSame('D1', $measureView->locations[1]->numberedRoad->roadNumber);
        $this->assertInstanceOf(StorageAreaApiView::class, $measureView->locations[1]->storageArea);
        $this->assertSame('Aire', $measureView->locations[1]->storageArea->description);

        $this->assertInstanceOf(RawGeoJSONApiView::class, $measureView->locations[2]->rawGeoJSON);
        $this->assertSame('Zone', $measureView->locations[2]->rawGeoJSON->label);
    }

    public function testFromViewWithoutVehicleSetAndPeriods(): void
    {
        $measure = new MeasureView(
            uuid: 'measure-uuid',
            type: 'noEntry',
            periods: null,
            vehicleSet: null,
            maxSpeed: null,
            locations: [],
        );

        $measureView = MeasureApiView::fromView($measure);

        $this->assertNull($measureView->vehicleSet);
        $this->assertNull($measureView->maxSpeed);
        $this->assertSame([], $measureView->periods);
        $this->assertSame([], $measureView->locations);
    }

    private function createGeneralInfo(): GeneralInfoView
    {
        return new GeneralInfoView(
            uuid: 'roc-uuid',
            identifier: 'F2025/001',
            organizationName: 'Ma collectivité',
            organizationLogo: null,
            organizationUuid: 'org-uuid',
            organizationAddress: null,
            status: 'draft',
            source: RegulationOrderRecordSourceEnum::API,
            regulationOrderUuid: 'ro-uuid',
            regulationOrderTemplateUuid: null,
            category: 'temporaryRegulation',
            subject: 'roadMaintenance',
            otherCategoryText: null,
            title: 'Travaux de voirie rue Exemple',
            startDate: new \DateTimeImmutable('2025-10-09T08:00:00+00:00'),
            endDate: new \DateTimeImmutable('2025-10-15T18:00:00+00:00'),
        );
    }
}

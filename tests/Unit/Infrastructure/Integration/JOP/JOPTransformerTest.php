<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Integration\JOP;

use App\Application\Integration\JOP\Command\ImportJOPRegulationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\SaveMeasureCommand;
use App\Application\Regulation\Command\SaveRegulationGeneralInfoCommand;
use App\Application\Regulation\Command\VehicleSet\SaveVehicleSetCommand;
use App\Application\RoadGeocoderInterface;
use App\Domain\Condition\Period\Enum\PeriodRecurrenceTypeEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use App\Domain\Regulation\Specification\CanUseRawGeoJSON;
use App\Domain\User\Organization;
use App\Infrastructure\Integration\JOP\JOPTransformer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class JOPTransformerTest extends TestCase
{
    public function testTransform(): void
    {
        $organization = $this->createMock(Organization::class);
        $roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);

        $geoJSON = [
            'features' => [
                [
                    'type' => 'Feature',
                    'properties' => [
                        'EVENEMENT' => 'PARA_Marathon',
                        'TYPE_PERI' => 'Rouge',
                        'DATE_DEBUT' => '2024/09/08 05:30:00.000',
                        'DATE_FIN' => '2024/09/08 14:00:00.000',
                        'COMPE' => 'Para',
                        'TRI' > 'MAR',
                    ],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => '<coords1>',
                        'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
                    ],
                ],
                [
                    'type' => 'Feature',
                    'properties' => [
                        'EVENEMENT' => str_repeat('a', 255),
                        'TYPE_PERI' => 'Rouge',
                        'DATE_DEBUT' => '2024/09/12 08:30:00.000',
                        'DATE_FIN' => '2024/09/12 11:00:00.000',
                    ],
                    'geometry' => [
                        'type' => 'Polygon',
                        'coordinates' => '<coords1>',
                        'crs' => ['type' => 'name', 'properties' => ['name' => 'EPSG:4326']],
                    ],
                ],
                // Feature with NULL geometry
                [
                    'type' => 'Feature',
                    'properties' => '...',
                    'geometry' => null,
                ],
            ],
        ];

        $roadGeocoder
            ->expects(self::once())
            ->method('findSectionsInArea')
            ->with(
                '{"type":"Polygon","coordinates":"<coords1>","crs":{"type":"name","properties":{"name":"EPSG:4326"}}}',
                [RoadGeocoderInterface::HIGHWAY],
            )
            ->willReturn('<sectionsGeometry1>');

        $translator
            ->expects(self::once())
            ->method('trans')
            ->with('jop.regulation_order.title')
            ->willReturn('Description arrêté JOP');

        $permissions = [CanUseRawGeoJSON::PERMISSION_NAME];

        $generalInfoCommand = new SaveRegulationGeneralInfoCommand();
        $generalInfoCommand->identifier = 'JOP2024-ZONES';
        $generalInfoCommand->category = RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value;
        $generalInfoCommand->subject = RegulationSubjectEnum::EVENT->value;
        $generalInfoCommand->title = 'Description arrêté JOP';
        $generalInfoCommand->organization = $organization;

        $locationCommand1 = new SaveLocationCommand();
        $locationCommand1->organization = $organization;
        $locationCommand1->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $locationCommand1->rawGeoJSON = new SaveRawGeoJSONCommand();
        $locationCommand1->rawGeoJSON->label = 'PARA_Marathon, ' . str_repeat('a', 255);
        $locationCommand1->rawGeoJSON->geometry = '<sectionsGeometry1>';
        $locationCommand1->permissions = $permissions;

        $vehicleSet1 = new SaveVehicleSetCommand();
        $vehicleSet1->allVehicles = true;
        $vehicleSet1->exemptedTypes = [VehicleTypeEnum::EMERGENCY_SERVICES->value, VehicleTypeEnum::OTHER->value];
        $vehicleSet1->otherExemptedTypeText = 'Zone Rouge';

        $period1 = new SavePeriodCommand();
        $period1->startDate = $period1->startTime = new \DateTimeImmutable('2024-09-08 05:30:00 Europe/Paris');
        $period1->endDate = $period1->endTime = new \DateTimeImmutable('2024-09-08 14:00:00 Europe/Paris');
        $period1->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $period1->dailyRange = null;
        $period1->timeSlots = [];

        $period2 = new SavePeriodCommand();
        $period2->startDate = $period2->startTime = new \DateTimeImmutable('2024-09-12 08:30:00 Europe/Paris');
        $period2->endDate = $period2->endTime = new \DateTimeImmutable('2024-09-12 11:00:00 Europe/Paris');
        $period2->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $period2->dailyRange = null;
        $period2->timeSlots = [];

        $measureCommand = new SaveMeasureCommand();
        $measureCommand->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand->locations = [$locationCommand1];
        $measureCommand->vehicleSet = $vehicleSet1;
        $measureCommand->periods = [$period1, $period2];
        $measureCommand->permissions = $permissions;

        $result = new ImportJOPRegulationCommand($generalInfoCommand, [$measureCommand]);

        $transformer = new JOPTransformer($logger, $roadGeocoder, $translator);

        $this->assertEquals($result, $transformer->transform($geoJSON, $organization));
    }
}

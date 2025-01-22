<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Litteralis;

use App\Application\Litteralis\Command\ImportLitteralisRegulationCommand;
use App\Application\Regulation\Command\Location\SaveLocationCommand;
use App\Application\Regulation\Command\Location\SaveRawGeoJSONCommand;
use App\Application\Regulation\Command\Period\SavePeriodCommand;
use App\Application\Regulation\Command\Period\SaveTimeSlotCommand;
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
use App\Infrastructure\IntegrationReport\CommonRecordEnum;
use App\Infrastructure\IntegrationReport\RecordTypeEnum;
use App\Infrastructure\IntegrationReport\Reporter;
use App\Infrastructure\Litteralis\LitteralisPeriodParser;
use App\Infrastructure\Litteralis\LitteralisRecordEnum;
use App\Infrastructure\Litteralis\LitteralisTransformer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LitteralisTransformerTest extends TestCase
{
    private $roadGeocoder;
    private $transformer;
    private $organization;
    private $logger;
    private $reporter;
    private $realWorldFeatures;
    private const feature = '{"type": "Feature", "id": "litteralis.493136", "geometry": {"type": "Polygon", "coordinates": [[[2.866303738, 50.6088816513], [2.866193844, 50.6090613967], [2.8659891868, 50.6093207548], [2.8658674961, 50.6094690524], [2.8658663892, 50.609470461], [2.8657726265, 50.6095952074], [2.8655193523, 50.6099280242], [2.8655162285, 50.6099328174], [2.8631138613, 50.6143199385], [2.8632470584, 50.6143494684], [2.8656480715, 50.6099647992], [2.8658996393, 50.6096342235], [2.8658997873, 50.6096340278], [2.8659930894, 50.609509894], [2.866114466, 50.6093619787], [2.8661149762, 50.6093613448], [2.8663213774, 50.6090997758], [2.8663240773, 50.6090959097], [2.8664367693, 50.608911587], [2.8664394331, 50.6089062743], [2.8665523929, 50.6086208051], [2.8664156265, 50.6085988914], [2.866303738, 50.6088816513]]]}, "geometry_name": "geometry", "properties": {"idemprise": 493136, "idarrete": 850503590, "shorturl": "https://dl.sogelink.fr/?n3omzTyS", "arretesrcid": "24-A-0126", "collectivitelibelle": "LILLE METROPOLE COMMUNAUTE URBAINE (LMCU)", "collectiviteid": null, "collectiviteagencelibelle": "MEL", "collectiviteagenceid": 117374, "documenttype": "ARRETE PERMANENT", "arretedebut": "2024-03-18T01:00:00Z", "arretefin": null, "empriseno": 1, "emprisetype": "CIRCULATION", "emprisedebut": null, "emprisefin": null, "mesures": "Limitation de vitesse", "localisations": " RUE DE LA MARLACQUE (FROMELLES) ENTRE LES PR0+066 ET PR0+750", "idagence": 117374, "fournisseur": "LIPRIME", "publicationinternet": true, "emetteurlibelle": "", "emetteurid": null, "categoriesmodele": "Réglementation permanente", "nommodele": "[MEL] - Arr\u00eat\u00e9 permanent", "parametresarrete": "entr\u00e9e en vigueur : \u00e0 la mise en place de la signalisation r\u00e9glementaire ; Date d\'envoi de la demande : 04/03/2024 00:00:00 ; Date de r\u00e9ception de la demande : 04/03/2024 00:00:00 ; Date de d\u00e9but de l\'arr\u00eat\u00e9 : 18/03/2024 00:00:00 ; ajout annexe : O ; nom annexe : plan ; MEP signa : Oui ; charg\u00e9 de MEP de la signalisation : Les services techniques de la collectivit\u00e9 ; MEL Avis suppl\u00e9mentaires : 0 ; Description des travaux : regulation description", "parametresemprise": "", "parametresmesures": "Limitation de vitesse | limite de vitesse : 70 km/h", "datecreation": "2024-03-15T10:39:00.269Z", "datemodification": "2024-03-15T10:39:00.739Z"}, "bbox": [2.8631138613, 50.6085988914, 2.8665523929, 50.6143494684]}';

    protected function setUp(): void
    {
        $tz = new \DateTimeZone('Etc/GMT-1'); // Independant of daylight saving time (DST)
        $this->roadGeocoder = $this->createMock(RoadGeocoderInterface::class);
        $this->transformer = new LitteralisTransformer($this->roadGeocoder, new LitteralisPeriodParser($tz));
        $this->organization = $this->createMock(Organization::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->reporter = new Reporter($this->logger);
        $this->realWorldFeatures = json_decode(file_get_contents(__DIR__ . '/LitteralisTransformerTest.data.json'), associative: true);
    }

    public function testTransformEmpty(): void
    {
        $command = $this->transformer->transform($this->reporter, 'identifier', [], $this->organization);
        $this->assertNull($command);
    }

    public function testTransformRealWorldExample(): void
    {
        $identifier = '173214#24/0194';

        $this->roadGeocoder
            ->expects(self::exactly(2))
            ->method('convertPolygonRoadToLines')
            ->withConsecutive([self::anything()], [self::anything()])
            ->willReturnOnConsecutiveCalls('geometry1', 'geometry2');

        $generalInfo = new SaveRegulationGeneralInfoCommand();
        $generalInfo->identifier = $identifier;
        $generalInfo->category = RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value;
        $generalInfo->subject = RegulationSubjectEnum::ROAD_MAINTENANCE->value;
        $generalInfo->title = "de chargement d'engin de chantier (URL : https://dl.sogelink.fr/?0dbjHha7)";
        $generalInfo->organization = $this->organization;

        $measureCommands = [];

        $measureCommand1 = new SaveMeasureCommand();
        $measureCommand1->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand1->permissions[] = CanUseRawGeoJSON::PERMISSION_NAME;

        $location1 = new SaveLocationCommand();
        $location1->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $rawGeoJSON1 = new SaveRawGeoJSONCommand();
        $rawGeoJSON1->label = "AVENUE DES CHÂTEAUX, DU 1 JUSQU'À LA PLACE DE LA DISTILLERIE;PLACE DE LA DISTILLERIE, DE LA RUE DE LA DISTILLERIE JUSQU'À LA RUE DU GÉNÉRAL LECLERC";
        $rawGeoJSON1->geometry = 'geometry1';
        $location1->rawGeoJSON = $rawGeoJSON1;
        $measureCommand1->addLocation($location1);

        $vehicleSet1 = new SaveVehicleSetCommand();
        $vehicleSet1->allVehicles = true;
        $measureCommand1->vehicleSet = $vehicleSet1;

        $period1 = new SavePeriodCommand();
        $period1->startDate = $period1->startTime = new \DateTimeImmutable('2024-03-18T01:00:00Z');
        $period1->endDate = $period1->endTime = new \DateTimeImmutable('2024-03-19T01:00:00Z');
        $period1->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $timeSlot1 = new SaveTimeSlotCommand();
        $timeSlot1->startTime = new \DateTimeImmutable('09:00');
        $timeSlot1->endTime = new \DateTimeImmutable('14:00');
        $period1->timeSlots = [$timeSlot1];
        $measureCommand1->periods[] = $period1;

        $measureCommands[] = $measureCommand1;

        $measureCommand2 = new SaveMeasureCommand();
        $measureCommand2->type = MeasureTypeEnum::NO_ENTRY->value;
        $measureCommand2->permissions[] = CanUseRawGeoJSON::PERMISSION_NAME;

        $location2 = new SaveLocationCommand();
        $location2->roadType = RoadTypeEnum::RAW_GEOJSON->value;
        $rawGeoJSON2 = new SaveRawGeoJSONCommand();
        $rawGeoJSON2->label = "PLACE DE LA DISTILLERIE, DE LA RUE DE LA DISTILLERIE JUSQU'À LA RUE DU GÉNÉRAL LECLERC";
        $rawGeoJSON2->geometry = 'geometry2';
        $location2->rawGeoJSON = $rawGeoJSON2;
        $measureCommand2->addLocation($location2);

        $vehicleSet2 = new SaveVehicleSetCommand();
        $vehicleSet2->allVehicles = false;
        $vehicleSet2->restrictedTypes = [VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, VehicleTypeEnum::OTHER->value];
        $vehicleSet2->otherRestrictedTypeText = 'bus de transport en commun';
        $vehicleSet2->heavyweightMaxWeight = 3.5;
        $measureCommand2->vehicleSet = $vehicleSet2;

        $period2 = new SavePeriodCommand();
        $period2->startDate = $period2->startTime = new \DateTimeImmutable('2024-03-18T01:00:00Z');
        $period2->endDate = $period2->endTime = new \DateTimeImmutable('2024-03-19T01:00:00Z');
        $period2->recurrenceType = PeriodRecurrenceTypeEnum::EVERY_DAY->value;
        $timeSlot2 = new SaveTimeSlotCommand();
        $timeSlot2->startTime = new \DateTimeImmutable('09:00');
        $timeSlot2->endTime = new \DateTimeImmutable('14:00');
        $period2->timeSlots = [$timeSlot2];
        $measureCommand2->periods[] = $period2;

        $measureCommands[] = $measureCommand2;

        $expectedCommand = new ImportLitteralisRegulationCommand($generalInfo, $measureCommands);

        $command = $this->transformer->transform($this->reporter, $identifier, $this->realWorldFeatures, $this->organization);
        $this->assertFalse($this->reporter->hasNewErrors());
        $this->assertEquals([], $this->reporter->getRecords());
        $this->assertEquals($expectedCommand, $command);
    }

    public function testTransformOtherCategory(): void
    {
        $feature = str_replace(
            '"categoriesmodele": "Réglementation permanente"',
            '"categoriesmodele": "special"',
            self::feature,
        );

        $features = [json_decode($feature, associative: true)];

        $command = $this->transformer->transform($this->reporter, 'identifier', $features, $this->organization);
        $this->assertSame(RegulationSubjectEnum::OTHER->value, $command->generalInfoCommand->subject);
        $this->assertSame('special', $command->generalInfoCommand->otherCategoryText);
    }

    public function testTransformDescription(): void
    {
        // Option 1: "Description des travaux"
        $features = [json_decode(self::feature, associative: true)];
        $command = $this->transformer->transform($this->reporter, 'identifier', $features, $this->organization);
        $this->assertSame('regulation description (URL : https://dl.sogelink.fr/?n3omzTyS)', $command->generalInfoCommand->title);

        // Option 2: "Description évènement"
        $feature = str_replace('Description des travaux : regulation description', 'Description évènement : event description', self::feature);
        $features = [json_decode($feature, associative: true)];
        $command = $this->transformer->transform($this->reporter, 'identifier', $features, $this->organization);
        $this->assertSame('event description (URL : https://dl.sogelink.fr/?n3omzTyS)', $command->generalInfoCommand->title);

        // Option 3: none is present, is 'nommodele' as a default
        $feature = str_replace('; Description des travaux : regulation description', '', self::feature);
        $features = [json_decode($feature, associative: true)];
        $command = $this->transformer->transform($this->reporter, 'identifier', $features, $this->organization);
        $this->assertSame('[MEL] - Arrêté permanent (URL : https://dl.sogelink.fr/?n3omzTyS)', $command->generalInfoCommand->title);
    }

    public function testTransformComplexMeasures(): void
    {
        // Values chosen to maximize test coverage

        $feature = str_replace(
            '"mesures": "Limitation de vitesse"',
            '"mesures": "SOGELINK - Circulation interdite ; Interdiction de dépasser ; Limitation de vitesse ; Interdiction de stationnement ; Neutralisation de voie"',
            self::feature,
        );

        $feature = str_replace(
            '"parametresmesures": "Limitation de vitesse | limite de vitesse : 70 km/h"',
            '"parametresmesures": "SOGELINK - Circulation interdite | dérogations : véhicules de l\'entreprise exécutant les travaux ; Interdiction de dépasser | véhicules concernés : poids lourds ; Limitation de vitesse 3 | limite de vitesse : 30 km/h ; Interdiction de stationnement 4 | horaires : de 14h à 18h"',
            $feature,
        );

        $feature = str_replace(
            '"parametresemprise": ""',
            '"parametresemprise": "jours et horaires : de 20H30 à 6H00"',
            $feature,
        );

        $features = [json_decode($feature, associative: true)];

        $command = $this->transformer->transform($this->reporter, 'identifier', $features, $this->organization);

        $this->assertNotNull($command, json_encode($this->reporter->getRecords()));

        $this->assertSame(MeasureTypeEnum::NO_ENTRY->value, $command->measureCommands[0]->type);
        $this->assertSame([VehicleTypeEnum::OTHER->value], $command->measureCommands[0]->vehicleSet->exemptedTypes);
        $this->assertSame("véhicules de l'entreprise exécutant les travaux", $command->measureCommands[0]->vehicleSet->otherExemptedTypeText);

        $this->assertSame(MeasureTypeEnum::SPEED_LIMITATION->value, $command->measureCommands[1]->type);
        $this->assertSame(30, $command->measureCommands[1]->maxSpeed);

        foreach ($command->measureCommands as $measureCommand) {
            $this->assertCount(1, $measureCommand->periods);
            $this->assertEquals(new \DateTimeImmutable('19:30'), $measureCommand->periods[0]->timeSlots[0]->startTime);
            $this->assertEquals(new \DateTimeImmutable('05:00'), $measureCommand->periods[0]->timeSlots[0]->endTime);
        }
    }

    public function testTransformWithErrors(): void
    {
        // Trigger ERROR_REGULATION_START_DATE_PARSING_FAILED when parsing general info and ERROR_DATE_PARSING_FAILED when parsing period
        $invalidDatesFeature = str_replace(
            '"arretedebut": "2024-03-18T01:00:00Z"',
            '"arretedebut": "BAD FORMAT"',
            self::feature,
        );

        // Trigger ERROR_REGULATION_END_DATE_PARSING_FAILED
        $invalidDatesFeature = str_replace(
            '"arretefin": null',
            '"arretefin": "BAD FORMAT"',
            $invalidDatesFeature,
        );

        // Trigger ERROR_MAX_SPEED_VALUE_MISSING
        $missingSpeedLimitValueFeature = str_replace(
            '"parametresmesures": "Limitation de vitesse | limite de vitesse : 70 km/h"',
            '"parametresmesures": ""',
            self::feature,
        );

        // Trigger ERROR_MAX_SPEED_VALUE_INVALID
        $invalidSpeedLimitValueFeature = str_replace(
            '70 km/h',
            'foo km/h',
            self::feature,
        );

        // Trigger ERROR_MEASURE_PARAMETER_MALFORMED
        $malformedParamFeature = str_replace(
            '"mesures": "Limitation de vitesse"',
            '"mesures": "Circulation interdite"',
            self::feature,
        );
        $malformedParamFeature = str_replace(
            '"parametresmesures": "Limitation de vitesse | limite de vitesse : 70 km/h"',
            '"parametresmesures": "Circulation interdite 1"',
            $malformedParamFeature,
        );

        // Trigger ERROR_MEASURE_PARAMETER_INCONSISTENT_NUMBER
        $inconsistentIndexFeature = str_replace(
            '"mesures": "Limitation de vitesse"',
            '"mesures": "Circulation interdite"',
            self::feature,
        );
        $inconsistentIndexFeature = str_replace(
            '"parametresmesures": "Limitation de vitesse | limite de vitesse : 70 km/h"',
            '"parametresmesures": "Circulation interdite 4 | véhicules concernés : poids lourds"',
            $inconsistentIndexFeature,
        );

        // Trigger ERROR_PERIOD_UNPARSABLE
        $periodUnparsableFeature = str_replace(
            '70 km/h',
            '70 km/h ; Limitation de vitesse | jours et horaires : foo',
            self::feature,
        );

        $features = [
            json_decode($invalidDatesFeature, associative: true),
            json_decode($missingSpeedLimitValueFeature, associative: true),
            json_decode($invalidSpeedLimitValueFeature, associative: true),
            json_decode($malformedParamFeature, associative: true),
            json_decode($inconsistentIndexFeature, associative: true),
            json_decode($periodUnparsableFeature, associative: true),
        ];

        $command = $this->transformer->transform($this->reporter, 'identifier', $features, $this->organization);
        $this->assertNull($command);
        $this->assertTrue($this->reporter->hasNewErrors());
        $this->assertEquals([
            [
                RecordTypeEnum::ERROR->value,
                [
                    RecordTypeEnum::ERROR->value => LitteralisRecordEnum::ERROR_DATE_PARSING_FAILED->value,
                    CommonRecordEnum::ATTR_REGULATION_ID->value => '24-A-0126',
                    CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'arretedebut' => 'BAD FORMAT',
                        'idemprise' => 493136,
                        'format' => \DateTimeInterface::ISO8601,
                    ],
                ],
            ],
            [
                RecordTypeEnum::ERROR->value,
                [
                    RecordTypeEnum::ERROR->value => LitteralisRecordEnum::ERROR_DATE_PARSING_FAILED->value,
                    CommonRecordEnum::ATTR_REGULATION_ID->value => '24-A-0126',
                    CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'arretefin' => 'BAD FORMAT',
                        'idemprise' => 493136,
                        'format' => \DateTimeInterface::ISO8601,
                    ],
                ],
            ],
            [
                RecordTypeEnum::ERROR->value,
                [
                    RecordTypeEnum::ERROR->value => LitteralisRecordEnum::ERROR_MAX_SPEED_VALUE_MISSING->value,
                    CommonRecordEnum::ATTR_REGULATION_ID->value => '24-A-0126',
                    CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'idemprise' => 493136,
                        'mesures' => 'Limitation de vitesse',
                    ],
                ],
            ],
            [
                RecordTypeEnum::ERROR->value,
                [
                    RecordTypeEnum::ERROR->value => LitteralisRecordEnum::ERROR_MAX_SPEED_VALUE_INVALID->value,
                    CommonRecordEnum::ATTR_REGULATION_ID->value => '24-A-0126',
                    CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'idemprise' => 493136,
                        'limite de vitesse' => 'foo km/h',
                    ],
                ],
            ],
            [
                RecordTypeEnum::ERROR->value,
                [
                    RecordTypeEnum::ERROR->value => LitteralisRecordEnum::ERROR_MEASURE_PARAMETER_MALFORMED->value,
                    CommonRecordEnum::ATTR_REGULATION_ID->value => '24-A-0126',
                    CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'idemprise' => 493136,
                        'param' => 'Circulation interdite 1',
                    ],
                ],
            ],
            [
                RecordTypeEnum::ERROR->value,
                [
                    RecordTypeEnum::ERROR->value => LitteralisRecordEnum::ERROR_MEASURE_PARAMETER_INCONSISTENT_NUMBER->value,
                    CommonRecordEnum::ATTR_REGULATION_ID->value => '24-A-0126',
                    CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'idemprise' => 493136,
                        'measureName' => 'Circulation interdite 4',
                        'expected' => 1,
                        'actual' => 4,
                    ],
                ],
            ],
            [
                RecordTypeEnum::ERROR->value,
                [
                    RecordTypeEnum::ERROR->value => LitteralisRecordEnum::ERROR_PERIOD_UNPARSABLE->value,
                    CommonRecordEnum::ATTR_REGULATION_ID->value => '24-A-0126',
                    CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'idemprise' => 493136,
                        'jours et horaires' => 'foo',
                    ],
                ],
            ],
        ], $this->reporter->getRecords());
    }

    public function testTransformNoMeasuresFound(): void
    {
        $features = [
            json_decode(str_replace('"mesures": "Limitation de vitesse"', '"mesures": "unknown measure"', self::feature), associative: true),
        ];

        $command = $this->transformer->transform($this->reporter, 'identifier', $features, $this->organization);
        $this->assertNull($command);
        $this->assertFalse($this->reporter->hasNewErrors());
        $this->assertEquals([
            [
                RecordTypeEnum::NOTICE->value,
                [
                    RecordTypeEnum::NOTICE->value => LitteralisRecordEnum::NOTICE_UNSUPPORTED_MEASURE->value,
                    CommonRecordEnum::ATTR_REGULATION_ID->value => '24-A-0126',
                    CommonRecordEnum::ATTR_DETAILS->value => [
                        'name' => 'unknown measure',
                        'idemprise' => 493136,
                    ],
                ],
            ],
            [
                RecordTypeEnum::NOTICE->value,
                [
                    RecordTypeEnum::NOTICE->value => LitteralisRecordEnum::NOTICE_NO_MEASURES_FOUND->value,
                    CommonRecordEnum::ATTR_REGULATION_ID->value => '24-A-0126',
                    CommonRecordEnum::ATTR_URL->value => 'https://dl.sogelink.fr/?n3omzTyS',
                ],
            ],
        ], $this->reporter->getRecords());
    }
}

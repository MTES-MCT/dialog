<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Templates;

use App\Application\Regulation\View\DatexLocationView;
use App\Application\Regulation\View\DatexTrafficRegulationView;
use App\Application\Regulation\View\DatexValidityConditionView;
use App\Application\Regulation\View\DatexVehicleConditionView;
use App\Application\Regulation\View\RegulationOrderDatexListItemView;
use App\Domain\Regulation\Enum\CritairEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Domain\Regulation\Enum\VehicleTypeEnum;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DatexRegulationsTemplateTest extends KernelTestCase
{
    private const TRO_NAMESPACE = 'http://datex2.eu/schema/3/trafficRegulation';
    private const COMMON_NAMESPACE = 'http://datex2.eu/schema/3/common';

    private function render(array $vehicleConditions): \DOMDocument
    {
        self::bootKernel();
        /** @var \Twig\Environment */
        $twig = static::getContainer()->get(\Twig\Environment::class);

        $content = $twig->render('api/regulations.xml.twig', [
            'publicationTime' => new \DateTimeImmutable('2026-10-05 08:00:00'),
            'regulationOrders' => [
                new RegulationOrderDatexListItemView(
                    uuid: '247edaa2-58d1-43de-9d33-9753bf6f4d30',
                    regulationOrderRecordUuid: '066c603a-ca34-75b9-8000-62c82cc0ed11',
                    regulationId: 'F01/2026#56456ff6-7e1c-4d24-aa09-9c650d7f6115',
                    organization: 'Autorité 1',
                    source: 'dialog',
                    title: 'Zone à trafic limité',
                    startDate: new \DateTimeImmutable('2026-11-15 23:00:00'),
                    endDate: new \DateTimeImmutable('2027-08-31 21:59:00'),
                    trafficRegulations: [
                        new DatexTrafficRegulationView(
                            type: 'noEntry',
                            locationConditions: [
                                new DatexLocationView(
                                    roadType: RoadTypeEnum::LANE->value,
                                    roadName: 'Rue de la Paix',
                                    roadNumber: null,
                                    rawGeoJSONLabel: null,
                                    geometry: '{"type":"LineString","coordinates":[[2.33,48.86],[2.34,48.87]]}',
                                ),
                            ],
                            vehicleConditions: $vehicleConditions,
                            validityConditions: [
                                new DatexValidityConditionView(
                                    new \DateTimeImmutable('2026-11-15 23:00:00'),
                                    new \DateTimeImmutable('2027-08-31 21:59:00'),
                                    [],
                                ),
                            ],
                        ),
                    ],
                ),
            ],
        ]);

        $xml = new \DOMDocument();
        $xml->loadXML($content, \LIBXML_NOBLANKS);

        return $xml;
    }

    private function createXPath(\DOMDocument $xml): \DOMXPath
    {
        $xpath = new \DOMXPath($xml);
        $xpath->registerNamespace('tro', self::TRO_NAMESPACE);
        $xpath->registerNamespace('com', self::COMMON_NAMESPACE);
        $xpath->registerNamespace('xsi', 'http://www.w3.org/2001/XMLSchema-instance');

        return $xpath;
    }

    private function assertSchemaValid(\DOMDocument $xml): void
    {
        $this->assertTrue($xml->schemaValidate(self::$kernel->getProjectDir() . '/docs/spec/datex2/DATEXII_3_D2Payload.xsd'));
    }

    public function testRestrictedAndExemptedVehiclesAreSeparated(): void
    {
        $xml = $this->render([
            new DatexVehicleConditionView(VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, maxWeight: 3.5),
            new DatexVehicleConditionView(VehicleTypeEnum::DIMENSIONS->value, maxWidth: 2, maxLength: 12, maxHeight: 2.4),
            new DatexVehicleConditionView(CritairEnum::CRITAIR_4->value),
            new DatexVehicleConditionView(VehicleTypeEnum::HAZARDOUS_MATERIALS->value),
            new DatexVehicleConditionView(VehicleTypeEnum::OTHER->value, otherTypeText: 'Trottinettes'),
            new DatexVehicleConditionView(VehicleTypeEnum::COMMERCIAL->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::EMERGENCY_SERVICES->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::BICYCLE->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::PEDESTRIANS->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::TAXI->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::CAR_SHARING->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::ROAD_MAINTENANCE_OR_CONSTRUCTION->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::CITY_LOGISTICS->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::DESSERTE_LOCALE->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::LOCAL_RESIDENT->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::OTHER->value, isExempted: true, otherTypeText: 'Convois exceptionnels'),
        ]);

        // Tous les codes exposés doivent exister dans les énumérations DATEX II
        $this->assertSchemaValid($xml);

        $xpath = $this->createXPath($xml);
        $conditionSets = $xpath->query('//tro:trafficRegulation/tro:condition/tro:conditions[tro:conditions[@xsi:type!="ValidityCondition" and @xsi:type!="LocationCondition"]]');
        $this->assertCount(2, $conditionSets);

        // Véhicules concernés : reliés par un OU, aucun n'est nié
        $restrictedSet = $conditionSets->item(0);
        $this->assertSame('or', $xpath->evaluate('string(tro:operator)', $restrictedSet));
        $this->assertSame(5.0, $xpath->evaluate('count(tro:conditions)', $restrictedSet));
        $this->assertSame(5.0, $xpath->evaluate('count(tro:conditions[tro:negate="false"])', $restrictedSet));

        // Dérogations : toutes niées et reliées par un ET, sinon la condition est vraie pour tout véhicule
        $exemptedSet = $conditionSets->item(1);
        $this->assertSame('and', $xpath->evaluate('string(tro:operator)', $exemptedSet));
        $this->assertSame(11.0, $xpath->evaluate('count(tro:conditions)', $exemptedSet));
        $this->assertSame(11.0, $xpath->evaluate('count(tro:conditions[tro:negate="true"])', $exemptedSet));

        $this->assertSame(
            ['bus', 'bicycle'],
            array_map(fn (\DOMNode $node) => $node->textContent, iterator_to_array($xpath->query('.//com:vehicleType', $exemptedSet))),
        );
        $this->assertSame(
            ['emergencyServices', 'taxi', 'carSharing', 'roadMaintenanceOrConstruction', 'cityLogistics'],
            array_map(fn (\DOMNode $node) => $node->textContent, iterator_to_array($xpath->query('.//com:vehicleUsage', $exemptedSet))),
        );
        $this->assertSame('pedestrians', $xpath->evaluate('string(.//tro:nonVehicularRoadUser)', $exemptedSet));
        $this->assertSame('destinationTraffic', $xpath->evaluate('string(.//tro:accessConditionType)', $exemptedSet));
        $this->assertSame('localResident', $xpath->evaluate('string(.//tro:driverCharacteristicsType)', $exemptedSet));
    }

    public function testExemptedVehiclesOnly(): void
    {
        $xml = $this->render([
            new DatexVehicleConditionView(VehicleTypeEnum::TAXI->value, isExempted: true),
            new DatexVehicleConditionView(VehicleTypeEnum::BICYCLE->value, isExempted: true),
        ]);

        $this->assertSchemaValid($xml);

        $xpath = $this->createXPath($xml);
        $conditionSets = $xpath->query('//tro:trafficRegulation/tro:condition/tro:conditions[tro:conditions[@xsi:type="VehicleCondition"]]');
        $this->assertCount(1, $conditionSets);
        $this->assertSame('and', $xpath->evaluate('string(tro:operator)', $conditionSets->item(0)));
        $this->assertSame(2.0, $xpath->evaluate('count(tro:conditions[tro:negate="true"])', $conditionSets->item(0)));
    }

    public function testRestrictedVehiclesOnly(): void
    {
        $xml = $this->render([
            new DatexVehicleConditionView(VehicleTypeEnum::HEAVY_GOODS_VEHICLE->value, maxWeight: 3.5),
            new DatexVehicleConditionView(VehicleTypeEnum::HAZARDOUS_MATERIALS->value),
        ]);

        $this->assertSchemaValid($xml);

        $xpath = $this->createXPath($xml);
        $conditionSets = $xpath->query('//tro:trafficRegulation/tro:condition/tro:conditions[tro:conditions[@xsi:type="VehicleCondition"]]');
        $this->assertCount(1, $conditionSets);
        $this->assertSame('or', $xpath->evaluate('string(tro:operator)', $conditionSets->item(0)));
        $this->assertSame(2.0, $xpath->evaluate('count(tro:conditions[tro:negate="false"])', $conditionSets->item(0)));
    }

    public function testAllVehicles(): void
    {
        $xml = $this->render([]);

        $this->assertSchemaValid($xml);

        $xpath = $this->createXPath($xml);
        // Seuls restent les ensembles de conditions de validité et de localisation
        $this->assertSame(2.0, $xpath->evaluate('count(//tro:trafficRegulation/tro:condition/tro:conditions)'));
    }
}

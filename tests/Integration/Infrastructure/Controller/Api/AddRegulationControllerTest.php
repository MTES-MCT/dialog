<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddRegulationControllerTest extends AbstractWebTestCase
{
    public function testAddRegulation(): void
    {
        $client = static::createClient();

        $payload = [
            'identifier' => 'F2025/publié',
            'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
            'category' => RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            'subject' => RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            'otherCategoryText' => null,
            'title' => 'Travaux de voirie rue Exemple',
            'measures' => [[
                'type' => 'speedLimitation',
                'maxSpeed' => 30,
                'createdAt' => '2025-10-09T08:00:00Z',
                'vehicleSet' => [
                    'allVehicles' => false,
                    'restrictedTypes' => ['heavyGoodsVehicle'],
                    'exemptedTypes' => ['commercial'],
                    'otherRestrictedTypeText' => 'string',
                    'otherExemptedTypeText' => 'string',
                    'heavyweightMaxWeight' => 0,
                    'maxWidth' => 0,
                    'maxLength' => 0,
                    'maxHeight' => 0,
                    'critairTypes' => ['critair2'],
                ],
                'periods' => [[
                    'startDate' => '2025-10-16T13:01:02.887Z',
                    'startTime' => '2025-10-16T13:01:02.887Z',
                    'endDate' => '2025-10-16T13:01:02.887Z',
                    'endTime' => '2025-10-16T13:01:02.887Z',
                    'recurrenceType' => 'everyDay',
                    'isPermanent' => true,
                    'dailyRange' => [
                        'recurrenceType' => 'everyDay',
                        'applicableDays' => ['monday'],
                    ],
                    'timeSlots' => [[
                        'startTime' => '2025-10-16T13:01:02.887Z',
                        'endTime' => '2025-10-16T13:01:02.887Z',
                    ]],
                ]],
                'locations' => [[
                    'roadType' => 'lane',
                    'namedStreet' => [
                        'cityCode' => '93070',
                        'cityLabel' => 'saint ouen sur seine',
                        'roadName' => 'rue eugène berthoud',
                        'direction' => 'BOTH',
                    ],
                ]],
            ]],
        ];

        $client->request(
            'POST',
            '/api/regulations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertSecurityHeaders();

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('uuid', $data);
    }

    public function testAddRegulationWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $payload = [
            'identifier' => 'API-INT-0002',
            'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
            'category' => RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            'subject' => RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            'otherCategoryText' => null,
            'title' => 'Travaux de voirie via API (invalid credentials)',
        ];

        $client->request(
            'POST',
            '/api/regulations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'aaa',
                'HTTP_X_CLIENT_SECRET' => 'bbb',
            ],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $response = $client->getResponse();
        $data = json_decode($response->getContent(), true);
        $this->assertSame(['message' => 'Unauthorized'], $data);
    }

    public function testAddRegulationValidationErrors(): void
    {
        $client = static::createClient();

        $payload = [
            'identifier' => '',
            'status' => RegulationOrderRecordStatusEnum::PUBLISHED->value,
            'category' => 'invalidCategory',
            'subject' => RegulationSubjectEnum::OTHER->value,
            'title' => '',
            'measures' => null,
        ];

        $client->request(
            'POST',
            '/api/regulations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(422);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame(422, $data['status']);
        $this->assertSame('Validation failed', $data['detail']);
        $this->assertIsArray($data['violations']);

        $paths = array_map(static fn (array $v) => $v['propertyPath'], $data['violations']);
        $this->assertContains('generalInfo.category', $paths);
        $this->assertContains('generalInfo.identifier', $paths);
        $this->assertContains('generalInfo.otherCategoryText', $paths);
        $this->assertContains('generalInfo.title', $paths);
        $this->assertContains('measureDtos', $paths);
    }

    public function testAddRegulationLaneGeocodingFailure(): void
    {
        $client = static::createClient();

        $payload = json_encode([
            'identifier' => 'API-INT-0004',
            'status' => RegulationOrderRecordStatusEnum::DRAFT->value,
            'category' => RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            'subject' => RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            'otherCategoryText' => null,
            'title' => 'Géocodage voie en échec',
            'measures' => [[
                'type' => 'noEntry',
                'locations' => [[
                    'roadType' => 'lane',
                    'namedStreet' => [
                        'isEntireStreet' => true,
                        'cityCode' => '59368',
                        'cityLabel' => 'La Madeleine (59110)',
                        'roadBanId' => '12345_6789',
                        'roadName' => 'Rue inconnue',
                    ],
                ]],
            ]],
        ]);

        $client->request(
            'POST',
            '/api/regulations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
            $payload,
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame(400, $data['status']);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue.', (string) $data['detail']);
    }

    public function testAddRegulationLaneOrganizationOutOfPerimeter(): void
    {
        $client = static::createClient();

        $payload = json_encode([
            'identifier' => 'API-INT-0005',
            'status' => RegulationOrderRecordStatusEnum::DRAFT->value,
            'category' => RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            'subject' => RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            'otherCategoryText' => null,
            'title' => 'Hors périmètre org',
            'measures' => [[
                'type' => 'noEntry',
                'locations' => [[
                    'roadType' => 'lane',
                    'namedStreet' => [
                        'cityCode' => '44195',
                        'cityLabel' => 'Savenay (44260)',
                        'roadBanId' => '44195_0137',
                        'roadName' => 'Route du Grand Brossais',
                        'fromHouseNumber' => '15',
                        'toHouseNumber' => '37bis',
                        'direction' => 'BOTH',
                    ],
                ]],
            ]],
        ]);

        $client->request(
            'POST',
            '/api/regulations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
            $payload,
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertSame(400, $data['status']);
        $this->assertStringStartsWith('L\'organisation "" ne semble pas avoir les compétences pour intervenir sur ce linéaire de route. S\'il s\'agit d\'une erreur, vous pouvez contacter le support DiaLog', (string) $data['detail']);
    }

    public function testNoLocationWithMultipleViolations(): void
    {
        $client = static::createClient();

        $payload = [
            'identifier' => 'API-LOC-0001',
            'status' => RegulationOrderRecordStatusEnum::DRAFT->value,
            'category' => RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            'subject' => RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            'title' => 'Aucune localisation',
            'measures' => [[
                'type' => 'speedLimitation',
                'maxSpeed' => 30,
                'periods' => [['isPermanent' => true]],
                'locations' => [[
                    'roadType' => 'lane',
                    // pas de namedStreet/departmentalRoad/nationalRoad/rawGeoJSON
                ]],
            ]],
        ];

        $client->request(
            'POST',
            '/api/regulations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
            json_encode($payload),
        );

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertIsArray($data);
        $this->assertSame(422, $data['status']);
        $this->assertSame('Cette valeur ne doit pas être vide.', $data['violations'][0]['title']);
        $this->assertSame('Cette valeur ne doit pas être vide.', $data['violations'][1]['title']);
        $this->assertSame('Un seul type de localisation doit être renseigné (voie nommée, route départementale, route nationale ou GeoJSON brut).', $data['violations'][2]['title']);
        $this->assertSame('La section de localisation doit correspondre à la valeur de type de voie (roadType).', $data['violations'][3]['title']);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class UpdateRegulationControllerTest extends AbstractWebTestCase
{
    private function getPayload(): array
    {
        return [
            'identifier' => 'FO1/2023',
            'status' => RegulationOrderRecordStatusEnum::DRAFT->value,
            'category' => RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            'subject' => RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            'otherCategoryText' => null,
            'title' => 'Titre mis à jour via API',
            'measures' => [[
                'type' => 'speedLimitation',
                'maxSpeed' => 50,
                'periods' => [[
                    'startDate' => '2025-10-16T13:01:02.887Z',
                    'recurrenceType' => 'everyDay',
                    'isPermanent' => true,
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
    }

    public function testUpdateRegulation(): void
    {
        $client = static::createClient();

        // FO1/2023 appartient à seineSaintDenisOrg et est un draft avec mesures
        $client->request(
            'PUT',
            '/api/regulations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
            json_encode($this->getPayload()),
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertSecurityHeaders();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('uuid', $data);
        $this->assertSame('FO1/2023', $data['identifier']);
        $this->assertSame(RegulationOrderRecordStatusEnum::DRAFT->value, $data['status']);

        // Vérifie que les mesures ont été remplacées
        $client->request(
            'GET',
            '/api/regulations/FO1/2023',
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Titre mis à jour via API', $data['title']);
        $this->assertCount(1, $data['measures']);
        $this->assertSame('speedLimitation', $data['measures'][0]['type']);
        $this->assertSame(50, $data['measures'][0]['maxSpeed']);
    }

    public function testUpdateRegulationAndPublish(): void
    {
        $client = static::createClient();

        $payload = $this->getPayload();
        $payload['status'] = RegulationOrderRecordStatusEnum::PUBLISHED->value;

        $client->request(
            'PUT',
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

        $this->assertResponseStatusCodeSame(200);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(RegulationOrderRecordStatusEnum::PUBLISHED->value, $data['status']);
    }

    public function testUpdateCannotUnpublish(): void
    {
        $client = static::createClient();

        // FO2/2023 appartient à seineSaintDenisOrg et est publié
        $payload = $this->getPayload();
        $payload['identifier'] = 'FO2/2023';
        $payload['status'] = RegulationOrderRecordStatusEnum::DRAFT->value;

        $client->request(
            'PUT',
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

        $this->assertResponseStatusCodeSame(400);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(400, $data['status']);
        $this->assertSame('Un arrêté publié ne peut pas être repassé en brouillon.', $data['detail']);
    }

    public function testUpdateReturns404ForAnotherOrganization(): void
    {
        $client = static::createClient();

        // L'arrêté '2025-01' appartient à dialogOrg, pas à seineSaintDenisOrg
        $payload = $this->getPayload();
        $payload['identifier'] = '2025-01';

        $client->request(
            'PUT',
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

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(404, $data['status']);
        $this->assertSame('Aucun arrêté de circulation trouvé pour l\'identifiant 2025-01 au sein de l\'organisation Département de Seine-Saint-Denis.', $data['detail']);
    }

    public function testUpdateReturns404WhenRegulationDoesNotExist(): void
    {
        $client = static::createClient();

        $payload = $this->getPayload();
        $payload['identifier'] = 'DOES-NOT-EXIST';

        $client->request(
            'PUT',
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

        $this->assertResponseStatusCodeSame(404);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(404, $data['status']);
        $this->assertSame('Aucun arrêté de circulation trouvé pour l\'identifiant DOES-NOT-EXIST au sein de l\'organisation Département de Seine-Saint-Denis.', $data['detail']);
    }

    public function testUpdateUnauthorizedWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/regulations',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'aaa',
                'HTTP_X_CLIENT_SECRET' => 'bbb',
            ],
            json_encode($this->getPayload()),
        );

        $this->assertResponseStatusCodeSame(401);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['message' => 'Unauthorized'], $data);
    }

    public function testUpdateRegulationValidationErrors(): void
    {
        $client = static::createClient();

        $payload = [
            'identifier' => 'FO1/2023',
            'status' => RegulationOrderRecordStatusEnum::DRAFT->value,
            'category' => 'invalidCategory',
            'subject' => RegulationSubjectEnum::OTHER->value,
            'title' => '',
            'measures' => null,
        ];

        $client->request(
            'PUT',
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
        $this->assertSame('Validation failed', $data['detail']);
        $this->assertIsArray($data['violations']);
    }
}

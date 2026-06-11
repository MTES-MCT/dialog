<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetRegulationControllerTest extends AbstractWebTestCase
{
    public function testGetRegulation(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            \sprintf('/api/regulations/%s', RegulationOrderFixture::TYPICAL_IDENTIFIER),
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertSecurityHeaders();

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(RegulationOrderFixture::TYPICAL_IDENTIFIER, $data['identifier']);
        $this->assertSame(RegulationOrderRecordStatusEnum::DRAFT->value, $data['status']);
        $this->assertArrayHasKey('category', $data);
        $this->assertArrayHasKey('title', $data);
        $this->assertArrayHasKey('organization', $data);
        $this->assertArrayHasKey('measures', $data);
        $this->assertIsArray($data['measures']);
        $this->assertNotEmpty($data['measures']);

        $measure = $data['measures'][0];
        $this->assertArrayHasKey('uuid', $measure);
        $this->assertArrayHasKey('type', $measure);
        $this->assertArrayHasKey('vehicleSet', $measure);
        $this->assertArrayHasKey('periods', $measure);
        $this->assertArrayHasKey('locations', $measure);
    }

    public function testGetReturns404ForAnotherOrganization(): void
    {
        $client = static::createClient();

        // L'arrêté '2025-01' appartient à dialogOrg, pas à seineSaintDenisOrg
        $client->request(
            'GET',
            '/api/regulations/2025-01',
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(404, $data['status']);
        $this->assertSame('Not Found', $data['detail']);
    }

    public function testGetReturns404WhenRegulationDoesNotExist(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/regulations/DOES-NOT-EXIST',
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(404, $data['status']);
        $this->assertSame('Not Found', $data['detail']);
    }

    public function testGetUnauthorizedWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            \sprintf('/api/regulations/%s', RegulationOrderFixture::TYPICAL_IDENTIFIER),
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'invalid',
                'HTTP_X_CLIENT_SECRET' => 'invalid',
            ],
        );

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['message' => 'Unauthorized'], $data);
    }
}

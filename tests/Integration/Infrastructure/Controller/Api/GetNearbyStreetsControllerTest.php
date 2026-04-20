<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetNearbyStreetsControllerTest extends AbstractWebTestCase
{
    private const array API_HEADERS = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_CLIENT_ID' => 'clientId',
        'HTTP_X_CLIENT_SECRET' => 'clientSecret',
    ];

    public function testNearbyStreets(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/nearby-streets',
            [],
            [],
            self::API_HEADERS,
            json_encode([
                'geometry' => ['type' => 'Point', 'coordinates' => [2.352222, 48.856614]],
                'radius' => 100,
                'limit' => 5,
            ]),
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertSecurityHeaders();

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertIsArray($data);

        if (\count($data) > 0) {
            $this->assertArrayHasKey('roadName', $data[0]);
            $this->assertArrayHasKey('distance', $data[0]);
        }
    }

    public function testNearbyStreetsWithInvalidJson(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/nearby-streets',
            [],
            [],
            self::API_HEADERS,
            '{invalid',
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Invalid JSON body', $data['error']);
    }

    public function testNearbyStreetsWithMissingGeometry(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/nearby-streets',
            [],
            [],
            self::API_HEADERS,
            json_encode(['radius' => 100]),
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('GeoJSON geometry required', $data['error']);
    }

    public function testNearbyStreetsWithInvalidGeometry(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/nearby-streets',
            [],
            [],
            self::API_HEADERS,
            json_encode(['geometry' => 'not-an-object']),
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Invalid GeoJSON geometry', $data['error']);
    }

    public function testNearbyStreetsWithEmptyBody(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/nearby-streets',
            [],
            [],
            self::API_HEADERS,
            '',
        );

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUnauthorizedWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request(
            'POST',
            '/api/nearby-streets',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'invalid',
                'HTTP_X_CLIENT_SECRET' => 'invalid',
            ],
            json_encode([
                'geometry' => ['type' => 'Point', 'coordinates' => [2.35, 48.85]],
            ]),
        );

        $this->assertResponseStatusCodeSame(401);
    }
}

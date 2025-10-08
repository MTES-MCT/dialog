<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddRegulationControllerTest extends AbstractWebTestCase
{
    public function testAddRegulationWithValidCredentials(): void
    {
        $client = static::createClient();

        $payload = [
            'identifier' => 'API-INT-0001',
            'category' => RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value,
            'subject' => RegulationSubjectEnum::ROAD_MAINTENANCE->value,
            'otherCategoryText' => null,
            'title' => 'Travaux de voirie via API',
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
        $this->assertArrayHasKey('message', $data);
        $this->assertMatchesRegularExpression('/^Regulation .+ created$/', $data['message']);
    }

    public function testAddRegulationWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $payload = [
            'identifier' => 'API-INT-0002',
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
            'category' => 'invalidCategory',
            'subject' => RegulationSubjectEnum::OTHER->value,
            'title' => '',
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
        $this->assertContains('identifier', $paths);
        $this->assertContains('category', $paths);
        $this->assertContains('title', $paths);
        $this->assertContains('otherCategoryText', $paths);
    }
}

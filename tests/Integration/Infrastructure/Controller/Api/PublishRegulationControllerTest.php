<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Domain\Regulation\Enum\RegulationOrderRecordStatusEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class PublishRegulationControllerTest extends AbstractWebTestCase
{
    public function testPublishRegulation(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            \sprintf('/api/regulations/publish/%s', RegulationOrderFixture::TYPICAL_IDENTIFIER),
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
        $this->assertSame(RegulationOrderRecordStatusEnum::PUBLISHED->value, $data['status']);
    }

    public function testPublishReturns404ForAnotherOrganization(): void
    {
        $client = static::createClient();

        // L'arrêté '2025-01' appartient à dialogOrg, pas à seineSaintDenisOrg
        $client->request(
            'PUT',
            '/api/regulations/publish/2025-01',
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        // Retourne 404 car l'arrêté n'est pas trouvé dans l'organisation de l'utilisateur
        $this->assertResponseStatusCodeSame(404);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(404, $data['status']);
        $this->assertSame('Not Found', $data['detail']);
    }

    public function testPublishReturns404WhenRegulationDoesNotExist(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/regulations/publish/DOES-NOT-EXIST',
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

    public function testPublishFailsWhenRegulationWithoutMeasures(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            '/api/regulations/publish/FO14/2023',
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(400);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(400, $data['status']);
        $this->assertSame('L\'arrêté ne peut pas être publié.', $data['detail']);
    }

    public function testPublishUnauthorizedWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request(
            'PUT',
            \sprintf('/api/regulations/publish/%s', RegulationOrderFixture::TYPICAL_IDENTIFIER),
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

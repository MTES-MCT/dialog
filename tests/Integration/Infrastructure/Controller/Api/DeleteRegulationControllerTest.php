<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class DeleteRegulationControllerTest extends AbstractWebTestCase
{
    public function testDeleteRegulation(): void
    {
        $client = static::createClient();

        // FO14/2023 appartient à seineSaintDenisOrg et est un draft sans mesures
        $client->request(
            'DELETE',
            '/api/regulations/FO14/2023',
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(204);
        $this->assertSecurityHeaders();
    }

    public function testDeleteReturns404ForAnotherOrganization(): void
    {
        $client = static::createClient();

        // L'arrêté '2025-01' appartient à dialogOrg, pas à seineSaintDenisOrg
        $client->request(
            'DELETE',
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

    public function testDeleteReturns404WhenRegulationDoesNotExist(): void
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
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

    public function testDeleteUnauthorizedWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request(
            'DELETE',
            '/api/regulations/FO1/2023',
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

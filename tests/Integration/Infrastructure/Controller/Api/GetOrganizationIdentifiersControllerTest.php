<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetOrganizationIdentifiersControllerTest extends AbstractWebTestCase
{
    public function testGetOrganizationIdentifiers(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/organization/identifiers',
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
        $expected = [
            'identifiers' => [
                0 => 'FO1/2023',
                1 => 'FO2/2023',
                2 => 'FO3/2023',
                3 => 'F2023/full-city',
                4 => 'F2023/no-locations',
                5 => 'FO14/2023',
                6 => 'F/CIFS/2023',
                7 => 'F2024/RAWGEOJSON',
                8 => '117374#24-A-0473',
            ],
        ];

        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($expected, $data);
    }

    public function testGetOrganizationIdentifiersForSaintOuenOrg(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/organization/identifiers',
            [],
            [],
            [
                'HTTP_X_CLIENT_ID' => 'saintOuenOrgClientId',
                'HTTP_X_CLIENT_SECRET' => 'saintOuenOrgClientSecret',
            ],
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('content-type', 'application/json');
        $this->assertSecurityHeaders();
        $expected = [
            'identifiers' => [],
        ];
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($expected, $data);
    }

    public function testGetOrganizationIdentifiersWithInvalidCredentials(): void
    {
        $client = static::createClient();

        $client->request(
            'GET',
            '/api/organization/identifiers',
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

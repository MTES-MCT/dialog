<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetAddressCompletionControllerTest extends AbstractWebTestCase
{
    public function testStreetAutoComplete(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/address-completion?search=Rue Eugène Berthoud&type=street');
        $response = $client->getResponse();

        $this->assertSame('application/json', $response->headers->get('content-type'));
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $expectedResult = [
            "addresses" => [
                "Rue Eugène Berthoud 93400 Saint-Ouen-sur-Seine",
                "Impasse Eugène Berthou 29480 Le Relecq-Kerhuon",
            ],
        ];

        $this->assertSame(json_decode($response->getContent(), true), $expectedResult);
    }

    public function testMunicipalityAutoComplete(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/address-completion?search=Le Mesnil&type=municipality');
        $response = $client->getResponse();

        $this->assertSame('application/json', $response->headers->get('content-type'));
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $expectedResult = [
            "addresses" => [
                "50580 Le Mesnil",
                "76240 Le Mesnil-Esnard",
                "78600 Le Mesnil-le-Roi",
            ],
        ];

        $this->assertSame(json_decode($response->getContent(), true), $expectedResult);
    }

    public function testBadRequest(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/address-completion');
        $client->getResponse();

        $this->assertResponseStatusCodeSame(400);
    }

    public function testBadRequestWithBadType(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/address-completion?search=Berthoud&type=azerty');
        $client->getResponse();

        $this->assertResponseStatusCodeSame(400);
    }
}

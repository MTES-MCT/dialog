<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstactWebTestCase;

final class RegulationDeleteControllerTest extends AbstactWebTestCase
{
    public function testDelete(): void
    {
        $client = $this->login();

        $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseStatusCodeSame(200);

        $client->request('DELETE', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseStatusCodeSame(204);

        $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/regulations/c1beed9a-6ec1-417a-abfd-0b5bd245616b');
        $this->assertResponseStatusCodeSame(204); // Idempotent
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/c1beed9a-6ec1-417a-abfd-0b5bd245616b');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

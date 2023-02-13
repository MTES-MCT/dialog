<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DuplicateRegulationControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDuplicate(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('Étape 5 sur 5 Récapitulatif', $crawler->filter('h2')->text());
    }

    public function testRegulationCannotBeDuplicated(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('POST', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationNotFound(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/e2beed9a-6ec1-417a-abfd-0b5bd245615a/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/e2beed9a-6ec1-417a-abfd-0b5bd245615a/duplicate');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/regulations/e2beed9a-6ec1-417a-abfd-0b5bd245615a/duplicate');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

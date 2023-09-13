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
        $client->request('POST', '/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();

        $this->assertSame('Arrêté permanent FO3/2023 (copie)', $crawler->filter('h2')->text());
        $this->assertSame('Copiée avec succès Vous pouvez modifier les informations que vous souhaitez dans cette copie de la réglementation.', $crawler->filter('div.fr-alert--success')->text());
        $location = $crawler->filter('[data-testid="location"]');

        // Location
        $this->assertSame('Paris 18e Arrondissement', $location->filter('h3')->text());
        $this->assertSame('Paris 18e Arrondissement (75018)', $location->filter('li')->eq(0)->text());
        $this->assertSame('Circulation alternée tous les jours pour tous les véhicules', $location->filter('li')->eq(1)->text());
        $this->assertSame('Circulation interdite le lundi de 08h00 à 22h00 pour les poids lourds de plus de 3.5 tonnes', $location->filter('li')->eq(2)->text());
    }

    public function testWithoutLocations(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/b1a3e982-39a1-4f0e-8a6f-ea2fd5e872c2/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $crawler = $client->followRedirect();

        $this->assertSame('Arrêté temporaire FO1/2023 (copie) (copie)', $crawler->filter('h2')->text());
        $this->assertSame('Copiée avec succès Vous pouvez modifier les informations que vous souhaitez dans cette copie de la réglementation.', $crawler->filter('div.fr-alert--success')->text());
    }

    public function testWithoutMeasures(): void
    {
        $client = $this->login();
        $crawler = $client->request('POST', '/regulations/0650037d-3e90-7a99-8000-a2099e71ae4a/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);
        $crawler = $client->followRedirect();

        $this->assertSame('Arrêté temporaire FO14/2023 (copie)', $crawler->filter('h2')->text());
        $this->assertSame('Copiée avec succès Vous pouvez modifier les informations que vous souhaitez dans cette copie de la réglementation.', $crawler->filter('div.fr-alert--success')->text());
    }

    public function testDuplicateAnAlreadyExistingIdentifier(): void
    {
        $client = $this->login();
        $client->request('POST', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('L\'identifiant de l\'arrêté est déjà utilisé', $crawler->filter('div.fr-alert--error')->text());
    }

    public function testDuplicateWithNoStartDateYet(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('POST', '/regulations/867d2be6-0d80-41b5-b1ff-8452b30a95f5/duplicate', [
            'token' => $this->generateCsrfToken($client, 'duplicate-regulation'),
        ]);

        $this->assertResponseStatusCodeSame(303);
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

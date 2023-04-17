<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class RegulationDetailControllerTest extends AbstractWebTestCase
{
    public function testDraftRegulationDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Arrêté temporaire FO1/2023', $crawler->filter('h2')->text());
        $this->assertMetaTitle("Arrêté temporaire FO1/2023 - DiaLog", $crawler);
        $this->assertSame('Brouillon', $crawler->filter('[data-testid="status-badge"]')->text());

        $generalInfo = $crawler->filter('[data-testid="general_info"]');
        $location = $crawler->filter('[data-testid="location"]');

        // General info
        $this->assertSame('Informations générales', $generalInfo->filter('h3')->text());
        $this->assertSame('DiaLog', $generalInfo->filter('li')->eq(0)->text());
        $this->assertSame('Description 1', $generalInfo->filter('li')->eq(1)->text());
        $this->assertSame('Du 13/03/2023 au 15/03/2023', $generalInfo->filter('li')->eq(2)->text());
        $this->assertSame('http://localhost/_fragment/regulations/general_info/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5', $generalInfo->filter('a')->link()->getUri());

        // Location
        $this->assertSame('Route du Grand Brossais', $location->filter('h3')->text());
        $this->assertSame('Savenay (44260)', $location->filter('li')->eq(0)->text());
        $this->assertSame('Route du Grand Brossais - du n° 15 au n° 37bis', $location->filter('li')->eq(1)->text());
        $this->assertSame('Circulation interdite pour tous les véhicules', $location->filter('li')->eq(2)->text());
        $this->assertSame('http://localhost/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/edit', $location->filter('a')->link()->getUri());

        // Actions
        $duplicateBtn = $crawler->selectButton('Dupliquer');
        $this->assertNotNull($duplicateBtn->attr('disabled'));
        $publishBtn = $crawler->selectButton('Publier');
        $this->assertNotNull($publishBtn->attr('disabled'));
    }

    public function testPublishedRegulationDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame('Publié', $crawler->filter('[data-testid="status-badge"]')->text());
        $this->assertSame(0, $crawler->selectButton('Modifier')->count()); // No edit buttons
    }

    public function testSeeAll(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);

        $crawler = $client->clickLink('Arrêtés de circulation');
        $this->assertRouteSame('app_regulations_list');

        // Temporary regulation order list is shown.
        $temporaryPanel = $crawler->filter('#temporary-panel');
        $this->assertStringContainsString('fr-tabs__panel--selected', $temporaryPanel->attr('class'));
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('GET', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/c1beed9a-6ec1-417a-abfd-0b5bd245616b');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/c1beed9a-6ec1-417a-abfd-0b5bd245616b');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

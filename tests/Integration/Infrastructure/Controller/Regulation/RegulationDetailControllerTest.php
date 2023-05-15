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
        $this->assertMetaTitle('Arrêté temporaire FO1/2023 - DiaLog', $crawler);
        $this->assertSame('Brouillon', $crawler->filter('[data-testid="status-badge"]')->text());

        $generalInfo = $crawler->filter('[data-testid="general_info"]');
        $location = $crawler->filter('[data-testid="location"]');

        // General info
        $this->assertSame('Description 1', $generalInfo->filter('h3')->text());
        $this->assertSame('DiaLog', $generalInfo->filter('li')->eq(0)->text());
        $this->assertSame('Description 1', $generalInfo->filter('li')->eq(1)->text());
        $this->assertSame('Du 13/03/2023 au 15/03/2023', $generalInfo->filter('li')->eq(2)->text());
        $editGeneralInfoForm = $generalInfo->selectButton('Modifier')->form();
        $this->assertSame('http://localhost/_fragment/regulations/general_info/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5', $editGeneralInfoForm->getUri());
        $this->assertSame('GET', $editGeneralInfoForm->getMethod());

        // Location
        $this->assertSame('Route du Grand Brossais', $location->filter('h3')->text());
        $this->assertSame('Savenay (44260)', $location->filter('li')->eq(0)->text());
        $this->assertSame('Route du Grand Brossais - du n° 15 au n° 37bis', $location->filter('li')->eq(1)->text());
        $this->assertSame('Circulation interdite', $location->filter('li')->eq(2)->text());
        $editLocationForm = $location->selectButton('Modifier')->form();
        $this->assertSame('http://localhost/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form', $editLocationForm->getUri());
        $this->assertSame('GET', $editLocationForm->getMethod());

        // Actions
        $duplicateForm = $crawler->selectButton('Dupliquer')->form();
        $this->assertSame($duplicateForm->getUri(), 'http://localhost/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/duplicate');
        $this->assertSame($duplicateForm->getMethod(), 'POST');

        $formDelete = $crawler->filter('aside')->selectButton('Supprimer')->form();
        $this->assertSame($formDelete->getUri(), 'http://localhost/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');
        $this->assertSame($formDelete->getMethod(), 'DELETE');

        $publishBtn = $crawler->selectButton('Publier');
        $this->assertSame(0, $crawler->selectButton('Valider')->count()); // Location form
        $this->assertSame('http://localhost/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/publish', $publishBtn->form()->getUri());
        $this->assertSame('POST', $publishBtn->form()->getMethod());
        $this->assertCount(1, $publishBtn->siblings()->filter('input[name="token"]'));
    }

    public function testPermanentRegulationDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Arrêté permanent FO3/2023', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Arrêté permanent FO3/2023 - DiaLog', $crawler);
    }

    public function testDraftRegulationDetailWithoutLocations(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/b1a3e982-39a1-4f0e-8a6f-ea2fd5e872c2');
        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);

        // Actions
        $saveButton = $crawler->selectButton('Valider');
        $this->assertSame("Indiquez la voie concernée et la ville. Si la restriction s'applique à toute la ville, indiquez la ville uniquement.", $crawler->filter('#location_form_address_help')->text());
        $this->assertSame(1, $saveButton->count()); // Location form
    }

    public function testPublishedRegulationDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame('Publié', $crawler->filter('[data-testid="status-badge"]')->text());
        $this->assertSame(0, $crawler->selectButton('Modifier')->count()); // No edit buttons
        $this->assertSame(0, $crawler->selectButton('Publier')->count());

        $formDelete = $crawler->filter('aside')->selectButton('Supprimer')->form();
        $this->assertSame($formDelete->getUri(), 'http://localhost/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertSame($formDelete->getMethod(), 'DELETE');
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

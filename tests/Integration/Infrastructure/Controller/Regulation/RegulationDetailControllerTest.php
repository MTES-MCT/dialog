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
        $this->assertSame('Réglementation - Route du Grand Brossais 44260 Savenay', $crawler->filter('h2')->text());
        $this->assertMetaTitle("Réglementation - Route du Grand Brossais 44260 Savenay - DiaLog", $crawler);
        $step1 = $crawler->filter('div.for-what');
        $step2 = $crawler->filter('div.where');

        // Step 1
        $this->assertSame('Description 1', $step1->filter('li')->eq(0)->text());
        $this->assertSame('Circulation interdite', $step1->filter('li')->eq(1)->text());
        $this->assertSame('du 13/03/2023 au 15/03/2023', $step1->filter('li')->eq(2)->text());
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5', $step1->filter('a')->link()->getUri());

        // Step 2
        $this->assertSame('Route du Grand Brossais 44260 Savenay', $step2->filter('li')->eq(0)->text());
        $this->assertSame('Numéro de début : 15', $step2->filter('li')->eq(1)->text());
        $this->assertSame('Numéro de fin : 37bis', $step2->filter('li')->eq(2)->text());
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/2', $step2->filter('a')->link()->getUri());

        // Status action
        $draftInput = $crawler->filter('input[id="publish_form_status_0"]')->first();
        $draftLabel = $draftInput->siblings()->filter('[for="publish_form_status_0"]')->first();
        $this->assertStringStartsWith('Sauvegarder le brouillon', $draftLabel->text());
        $publishedInput = $crawler->filter('input[id="publish_form_status_1"]')->first();
        $publishedLabel = $publishedInput->siblings()->filter('[for="publish_form_status_1"]')->first();
        $this->assertStringStartsWith('Valider la réglementation', $publishedLabel->text());
    }

    public function testSaveDraft(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Sauvegarder');

        $form = $saveButton->form();
        $form["publish_form[status]"] = "draft";
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_list');
    }

    public function testSavePublished(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Sauvegarder');

        $form = $saveButton->form();
        $form["publish_form[status]"] = "published";
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_list');
    }

    public function testPublishedRegulationDetail(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Réglementation - Avenue de Fonneuve 82000 Montauban', $crawler->filter('h2')->text());

        $this->assertSame(0, $crawler->filter('form')->count()); // No form found
    }

    public function testSeeAll(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);

        $crawler = $client->clickLink('Toutes les réglementations');
        $this->assertRouteSame('app_regulations_list');

        // Temporary regulation order list is shown.
        $temporaryPanel = $crawler->filter('#temporary-panel');
        $this->assertStringContainsString('fr-tabs__panel--selected', $temporaryPanel->attr('class'));
    }

    public function testCantBePublished(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Sauvegarder');

        $form = $saveButton->form();
        $form["publish_form[status]"] = "published";
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame('La réglementation ne peut pas être publiée. Vérifiez qu\'elle a correctement été créée.', $crawler->filter('div.fr-alert')->text());
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

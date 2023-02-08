<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Steps;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class Step5ControllerTest extends AbstractWebTestCase
{
    public function testPageState(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Étape 5 sur 5 Récapitulatif', $crawler->filter('h2')->text());

        $step1 = $crawler->filter('div.for-what');
        $step2 = $crawler->filter('div.where');
        $step3 = $crawler->filter('div.when');
        $step4 = $crawler->filter('div.vehicles');

        // Step 1
        $this->assertSame('Description 1', $step1->filter('li')->eq(0)->text());
        $this->assertSame('Circulation interdite', $step1->filter('li')->eq(1)->text());
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5', $step1->filter('a')->link()->getUri());

        // Step 2
        $this->assertSame('Ville : 44260 Savenay', $step2->filter('li')->eq(0)->text());
        $this->assertSame('Rue : du 15 au 37bis, Route du Grand Brossais', $step2->filter('li')->eq(1)->text());
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/2', $step2->filter('a')->link()->getUri());

        // Step 3
        $this->assertSame('du 08/12/2022 au 18/12/2022', $step3->filter('li')->eq(0)->text());
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/3', $step3->filter('a')->link()->getUri());

        // Step 4
        $this->assertSame('http://localhost/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/4', $step4->filter('a')->link()->getUri());
        $this->assertCount(0, $step4->filter('li'));

        // Status action
        $draftInput = $crawler->filter('input[id="step5_form_status_0"]')->first();
        $draftLabel = $draftInput->siblings()->filter('[for="step5_form_status_0"]')->first();
        $this->assertStringStartsWith('Sauvegarder le brouillon', $draftLabel->text());
        $publishedInput = $crawler->filter('input[id="step5_form_status_1"]')->first();
        $publishedLabel = $publishedInput->siblings()->filter('[for="step5_form_status_1"]')->first();
        $this->assertStringStartsWith('Valider la réglementation', $publishedLabel->text());
    }

    public function testSaveDraft(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Sauvegarder');

        $form = $saveButton->form();
        $form["step5_form[status]"] = "draft";
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_list');
    }

    public function testSavePublished(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Sauvegarder');

        $form = $saveButton->form();
        $form["step5_form[status]"] = "published";
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulation_detail');
    }

    public function testCantBePublished(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/5');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Sauvegarder');

        $form = $saveButton->form();
        $form["step5_form[status]"] = "published";
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame('La réglementation ne peut pas être publiée. Vérifiez qu\'elle a correctement été créée.', $crawler->filter('div.fr-alert')->text());
    }

    public function testPrevious(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Précédent');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_4', ['uuid' => 'e413a47e-5928-4353-a8b2-8b7dda27f9a5']);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/form/c1beed9a-6ec1-417a-abfd-0b5bd245616b/5');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/form/aaaaaaaa/5');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testUxEnhancements(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/5');
        $this->assertResponseStatusCodeSame(200);

        $backLink = $crawler->selectLink('Précédent');
        $this->assertNotNull($backLink->closest('turbo-frame[id="step-content"][data-turbo-action="advance"][autoscroll]'));
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/5');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

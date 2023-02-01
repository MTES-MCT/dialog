<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Steps;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class Step3ControllerTest extends AbstractWebTestCase
{
    public function testAdd(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/3'); // Regulation Order Record without overall period

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Étape 3 sur 4 Période', $crawler->filter('h2')->text());
        $this->assertSame('Étape suivante : Véhicules concernés', $crawler->filter('p.fr-stepper__details')->text());

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form["step3_form[startPeriod]"] = "2022-12-07T00:00";
        $form["step3_form[endPeriod]"] = "2022-12-17T23:59";

        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_4');
    }

    public function testInvalidPeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/3');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form["step3_form[startPeriod]"] = "mauvais format";
        $form["step3_form[endPeriod]"] = "mauvais format";

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(2, $crawler->filter('[id^="step3_form_"][id$="_error"]'));
        $this->assertSame("Veuillez saisir une date et une heure valides.", $crawler->filter('#step3_form_startPeriod_error')->text());
        $this->assertSame("Veuillez saisir une date et une heure valides.", $crawler->filter('#step3_form_endPeriod_error')->text());

        $form["step3_form[startPeriod]"] = "2022-12-07T00:00";
        $form["step3_form[endPeriod]"] = "2022-12-05T23:59";

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(1, $crawler->filter('[id^="step3_form_"][id$="_error"]'));
        $this->assertCount(1, $crawler->filter('#step3_form_endPeriod_error')); // Cette valeur doit être supérieure à 7 déc. 2022.
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/form/c1beed9a-6ec1-417a-abfd-0b5bd245616b/3');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/form/aaaaaaaa/3');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testPrevious(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/3');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Précédent');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_2', ['uuid' => '4ce75a1f-82f3-40ee-8f95-48d0f04446aa']);
    }

    public function testUxEnhancements(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/3');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Suivant');
        $this->assertNotNull($saveButton->closest('turbo-frame[id="step-content"][data-turbo-action="advance"][autoscroll]'));
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/3');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

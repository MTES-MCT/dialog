<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Steps;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class Step3ControllerTest extends AbstractWebTestCase
{
    public function provideAdd(): array {
        return [
            // Cover all valid cases.
            [
                // Start date only
                'startDate' => '2022-12-07',
                'startTime' => '',
                'endDate' => '',
                'endTime' => '',
            ],
            [
                // Start date, and end date in the future
                'startDate' => '2022-12-07',
                'startTime' => '',
                'endDate' => '2022-12-17',
                'endTime' => '',
            ],
            [
                // Same date, with start time only
                'startDate' => '2022-12-07',
                'startTime' => '09:00',
                'endDate' => '2022-12-07',
                'endTime' => '',
            ],
            [
                // Same date, with end time only
                'startDate' => '2022-12-07',
                'startTime' => '',
                'endDate' => '2022-12-07',
                'endTime' => '06:00',
            ],
            [
                // Same date, with end time in the future
                'startDate' => '2022-12-07',
                'startTime' => '09:00',
                'endDate' => '2022-12-07',
                'endTime' => '10:00',
            ],
        ];
    }

    /**
     * @dataProvider provideAdd
     */
    public function testAdd(string $startDate, string $startTime, string $endDate, string $endTime): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/3'); // Regulation Order Record without overall period

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Étape 3 sur 4 Période', $crawler->filter('h2')->text());
        $this->assertSame('Étape suivante : Véhicules concernés', $crawler->filter('p.fr-stepper__details')->text());

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form["step3_form[startDate]"] = $startDate;
        $form["step3_form[startTime]"] = $startTime;
        $form["step3_form[endDate]"] = $endDate;
        $form["step3_form[endTime]"] = $endTime;

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

        $form["step3_form[startDate]"] = "mauvais format";
        $form["step3_form[startTime]"] = "mauvais format";
        $form["step3_form[endDate]"] = "mauvais format";
        $form["step3_form[endTime]"] = "mauvais format";
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(4, $crawler->filter('[id^="step3_form_"][id$="_error"]'));
        $this->assertSame("Veuillez entrer une date valide.", $crawler->filter('#step3_form_startDate_error')->text());
        $this->assertSame("Veuillez saisir une heure valide.", $crawler->filter('#step3_form_startTime_error')->text());
        $this->assertSame("Veuillez entrer une date valide.", $crawler->filter('#step3_form_endDate_error')->text());
        $this->assertSame("Veuillez saisir une heure valide.", $crawler->filter('#step3_form_endTime_error')->text());

        $form["step3_form[startDate]"] = "2022-12-07";
        $form["step3_form[startTime]"] = "";
        $form["step3_form[endDate]"] = "2022-12-05";
        $form["step3_form[endTime]"] = "";
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(1, $crawler->filter('[id^="step3_form_"][id$="_error"]'));
        $this->assertSame("La date de fin doit être après le 07/12/2022.", $crawler->filter('#step3_form_endDate_error')->text());

        $form["step3_form[startDate]"] = "2022-12-07";
        $form["step3_form[startTime]"] = "16:00";
        $form["step3_form[endDate]"] = "2022-12-07";
        $form["step3_form[endTime]"] = "15:59";
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(1, $crawler->filter('[id^="step3_form_"][id$="_error"]'));
        $this->assertSame("L'heure de fin doit être après 16h00.", $crawler->filter('#step3_form_endTime_error')->text());

        $form["step3_form[startDate]"] = "2022-12-07";
        $form["step3_form[startTime]"] = "";
        $form["step3_form[endDate]"] = "";
        $form["step3_form[endTime]"] = "15:59";
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(1, $crawler->filter('[id^="step3_form_"][id$="_error"]'));
        $this->assertSame("Veuillez indiquer une date de fin, ou retirer l'heure de fin.", $crawler->filter('#step3_form_endDate_error')->text());
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

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Step3ControllerTest extends WebTestCase
{
    public function testAdd(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/3'); // Regulation Order Record without overall period

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Étape 3 sur 5 Période', $crawler->filter('h2')->text());
        $this->assertSame('Étape suivante : Véhicules concernés', $crawler->filter('p.fr-stepper__details')->text());

        $saveButton = $crawler->selectButton("Suivant");
        $form = $saveButton->form();
        $form["step3_form[startPeriod]"] = "2022-12-07";
        $form["step3_form[endPeriod]"] = "2022-12-17";

        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_4');
    }

    public function testInvalidPeriod(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/3');

        $saveButton = $crawler->selectButton("Suivant");
        $form = $saveButton->form();
        $form["step3_form[startPeriod]"] = "mauvais format";
        $form["step3_form[endPeriod]"] = "mauvais format";

        $crawler = $client->submit($form);
        $this->assertCount(2, $crawler->filter('[id^="step3_form_"][id$="_error"]'));
        $this->assertSame("Veuillez entrer une date valide.", $crawler->filter('#step3_form_startPeriod_error')->text());
        $this->assertSame("Veuillez entrer une date valide.", $crawler->filter('#step3_form_endPeriod_error')->text());

        $form["step3_form[startPeriod]"] = "2022-12-07";
        $form["step3_form[endPeriod]"] = "2022-12-05";

        $crawler = $client->submit($form);
        $this->assertCount(1, $crawler->filter('[id^="step3_form_"][id$="_error"]'));
        $this->assertCount(1, $crawler->filter('#step3_form_endPeriod_error')); // Cette valeur doit être supérieure à 7 déc. 2022.
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/aaaaaaaa-0000-1111-2222-bbbbbbbbbbbb/3');

        $this->assertResponseStatusCodeSame(404);
    }
}

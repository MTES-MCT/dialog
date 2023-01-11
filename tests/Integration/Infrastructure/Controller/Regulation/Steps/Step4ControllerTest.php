<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Step4ControllerTest extends WebTestCase
{
    public function testAdd(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/4');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Étape 4 sur 5 Véhicules concernés', $crawler->filter('h2')->text());
        $this->assertSame('Étape suivante : Récapitulatif', $crawler->filter('p.fr-stepper__details')->text());

        $saveButton = $crawler->selectButton("Suivant");
        $form = $saveButton->form();
        $form["step4_form[maxWeight]"] = "3.5";
        $form["step4_form[maxHeight]"] = "2.80";
        $form["step4_form[maxWidth]"] = "2";
        $form["step4_form[maxLength]"] = "9";

        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_5');
    }

    public function testInvalidVehicleCharacteristics(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/4');

        $saveButton = $crawler->selectButton("Suivant");
        $form = $saveButton->form();
        $form["step4_form[maxWeight]"] = "not a number";
        $form["step4_form[maxHeight]"] = "not a number";
        $form["step4_form[maxWidth]"] = "not a number";
        $form["step4_form[maxLength]"] = "not a number";

        $crawler = $client->submit($form);
        $this->assertCount(4, $crawler->filter('[id^="step4_form_"][id$="_error"]'));
        $this->assertSame("Veuillez saisir un nombre.", $crawler->filter('#step4_form_maxWeight_error')->text());
        $this->assertSame("Veuillez saisir un nombre.", $crawler->filter('#step4_form_maxHeight_error')->text());
        $this->assertSame("Veuillez saisir un nombre.", $crawler->filter('#step4_form_maxWidth_error')->text());
        $this->assertSame("Veuillez saisir un nombre.", $crawler->filter('#step4_form_maxLength_error')->text());

        $form["step4_form[maxWeight]"] = "-1";
        $form["step4_form[maxHeight]"] = "-12";
        $form["step4_form[maxWidth]"] = "-6";
        $form["step4_form[maxLength]"] = "-1.23";

        $crawler = $client->submit($form);
        $this->assertCount(4, $crawler->filter('[id^="step4_form_"][id$="_error"]'));
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#step4_form_maxWeight_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#step4_form_maxHeight_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#step4_form_maxWidth_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#step4_form_maxLength_error')->text());

        $form["step4_form[maxWeight]"] = "0";
        $form["step4_form[maxHeight]"] = "0";
        $form["step4_form[maxWidth]"] = "0";
        $form["step4_form[maxLength]"] = "0";

        $crawler = $client->submit($form);
        $this->assertCount(4, $crawler->filter('[id^="step4_form_"][id$="_error"]'));
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#step4_form_maxWeight_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#step4_form_maxHeight_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#step4_form_maxWidth_error')->text());
        $this->assertSame("Cette valeur doit être strictement positive.", $crawler->filter('#step4_form_maxLength_error')->text());
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/c1beed9a-6ec1-417a-abfd-0b5bd245616b/4');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/aaaaaaaa/4');

        $this->assertResponseStatusCodeSame(400);
    }
}

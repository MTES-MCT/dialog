<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Step1ControllerTest extends WebTestCase
{
    public function testAdd(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Étape 1 sur 5 Restriction', $crawler->filter('h2')->text());
        $this->assertSame('Étape suivante : Localisation', $crawler->filter('p.fr-stepper__details')->text());

        $saveButton = $crawler->selectButton("Suivant");
        $form = $saveButton->form();
        $form["step1_form[description]"] = "Interdiction de circuler dans Paris";
        $form["step1_form[issuingAuthority]"] = "Ville de Paris";

        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);

        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_2');
    }

    public function testBadRequest(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form');

        $saveButton = $crawler->selectButton("Suivant");
        $form = $saveButton->form();

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step1_form_description_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step1_form_issuingAuthority_error')->text());
    }
}

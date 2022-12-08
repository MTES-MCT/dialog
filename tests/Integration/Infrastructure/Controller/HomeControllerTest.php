<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testHome(): void
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Réglementations', $crawler->filter('h3')->text());
        $this->assertCount(1, $crawler->filter('[data-test=regulations_item]'));
        $this->assertCount(0, $crawler->filter('[data-test=regulations_empty]'));
        $this->assertCount(0, $crawler->filter("#regulation_order_description_error"));
        $this->assertCount(0, $crawler->filter("#regulation_order_issuingAuthority_error"));

        $saveButton = $crawler->selectButton("Enregistrer");
        $form = $saveButton->form();
        $form["regulation_order[description]"] = "Interdiction de circuler dans Paris";
        $form["regulation_order[issuingAuthority]"] = "Ville de Paris";
        $form["regulation_order[startPeriod]"] = "2022-12-07";
        $form["regulation_order[endPeriod]"] = "2022-12-17";

        $client->submit($form);
        $this->assertResponseRedirects("/", 302);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(2, $crawler->filter("[data-test=regulations_item]"));
        $this->assertCount(0, $crawler->filter('[data-test=regulations_empty]'));
    }

    public function testHomeInvalid(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton("Enregistrer");
        $form = $saveButton->form();
        $form["regulation_order[description]"] = "";
        $form["regulation_order[issuingAuthority]"] = "";

        $crawler = $client->submit($form);
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#regulation_order_description_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#regulation_order_issuingAuthority_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#regulation_order_startPeriod_error')->text());
    }

    public function testInvalidPeriod(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton("Enregistrer");
        $form = $saveButton->form();
        $form["regulation_order[startPeriod]"] = "mauvais format";
        $form["regulation_order[endPeriod]"] = "mauvais format";

        $crawler = $client->submit($form);
        $this->assertSame("Veuillez entrer une date valide.", $crawler->filter('#regulation_order_startPeriod_error')->text());
        $this->assertSame("Veuillez entrer une date valide.", $crawler->filter('#regulation_order_endPeriod_error')->text());

        $form["regulation_order[startPeriod]"] = "2022-12-07";
        $form["regulation_order[endPeriod]"] = "2022-12-05";

        $crawler = $client->submit($form);
        $this->assertCount(1, $crawler->filter('#regulation_order_endPeriod_error')); // Cette valeur doit être supérieure à 7 déc. 2022.
    }
}

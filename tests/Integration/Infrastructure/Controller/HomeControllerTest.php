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
        $this->assertResponseStatusCodeSame(200);
        $this->assertCount(1, $crawler->filter('#regulation_order_description_error'));
        $this->assertCount(1, $crawler->filter("#regulation_order_issuingAuthority_error"));
    }
}

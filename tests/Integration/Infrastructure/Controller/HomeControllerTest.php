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
        $this->assertSame(0, $crawler->filter('[data-test=regulations_item]')->count());
        $this->assertSame(1, $crawler->filter('[data-test=regulations_empty]')->count());

        $saveButton = $crawler->selectButton("Enregistrer");
        $form = $saveButton->form();
        $form["regulation_order[description]"] = "Interdiction de circuler dans Paris";
        $form["regulation_order[issuingAuthority]"] = "Ville de Paris";

        $client->submit($form);
        $this->assertResponseRedirects("/", 302);
        
        $crawler = $client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame(1, $crawler->filter("[data-test=regulations_item]")->count());
        $this->assertSame(0, $crawler->filter('[data-test=regulations_empty]')->count());
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
        $client->submit($form);
        $this->assertResponseStatusCodeSame(400);
    }
}

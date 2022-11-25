<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use App\Domain\RegulationOrder\Repository\RegulationOrderRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testHome(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('DiaLog', $crawler->filter('h1')->text());
        $this->assertSame('Digitaliser, diagnostiquer et diffuser la réglementation de circulation', $crawler->filter('h2')->text());
    }

    public function testHomeForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');
        
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame("Ajouter une réglementation", $crawler->filter("h3")->eq(1)->text());
        
        $saveButton = $crawler->selectButton("Enregistrer");
        
        $form = $saveButton->form();
        $form["regulation_order[description]"] = "Interdiction de circuler dans Paris";
        $form["regulation_order[issuingAuthority]"] = 'Ville de Paris';
        
        $client->submit($form);

        $crawler = $client->request('GET', '/');

        $this->assertSame(1, $crawler->filter("li")->count());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Step2ControllerTest extends WebTestCase
{
    public function testAdd(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/2');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Étape 2 sur 5 Localisation', $crawler->filter('h2')->text());
        $this->assertSame('Étape suivante : Période', $crawler->filter('p.fr-stepper__details')->text());

        $saveButton = $crawler->selectButton("Suivant");
        $form = $saveButton->form();

        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_3');
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/aaaaaaaa-0000-1111-2222-bbbbbbbbbbbb/2');

        $this->assertResponseStatusCodeSame(404);
    }
}

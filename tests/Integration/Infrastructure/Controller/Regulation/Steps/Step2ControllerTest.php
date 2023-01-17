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
        $form['step2_form[postalCode]'] = '44260';
        $form['step2_form[city]'] = 'Savenay';
        $form['step2_form[roadName]'] = 'Route du Grand Brossais';
        $form['step2_form[fromHouseNumber]'] = '15';
        $form['step2_form[toHouseNumber]'] = '37bis';

        $client->submit($form);
        $this->assertResponseStatusCodeSame(302);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_3');
    }

    public function testInvalidForm(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/2');

        $this->assertResponseStatusCodeSame(200);
        $saveButton = $crawler->selectButton("Suivant");
        $form = $saveButton->form();
        $form['step2_form[postalCode]'] = '';
        $form['step2_form[city]'] = '';
        $form['step2_form[roadName]'] = '';
        $form['step2_form[fromHouseNumber]'] = '';
        $form['step2_form[toHouseNumber]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_postalCode_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_city_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_roadName_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_fromHouseNumber_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_toHouseNumber_error')->text());
    }

    private function provideInvalidPostalCode(): array {
        return [
            ['4426'],
            ['442600'],
            ['notanumber'],
        ];
    }

    /**
     * @dataProvider provideInvalidPostalCode
     */
    public function testInvalidPostalCode(string $postalCode): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/e413a47e-5928-4353-a8b2-8b7dda27f9a5/2');

        $this->assertResponseStatusCodeSame(200);
        $saveButton = $crawler->selectButton("Suivant");
        $form = $saveButton->form();
        $form['step2_form[postalCode]'] = $postalCode;
        $form['step2_form[city]'] = 'Savenay';
        $form['step2_form[roadName]'] = 'Route du Grand Brossais';
        $form['step2_form[fromHouseNumber]'] = '15';
        $form['step2_form[toHouseNumber]'] = '37bis';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame("Cette valeur n'est pas valide. Un code postal est composé de 5 chiffres.", $crawler->filter('#step2_form_postalCode_error')->text());
    }


    public function testRegulationOrderRecordNotFound(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/c1beed9a-6ec1-417a-abfd-0b5bd245616b/2');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/aaaaaaaa/2');

        $this->assertResponseStatusCodeSame(400);
    }
}

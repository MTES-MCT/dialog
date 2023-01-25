<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class Step2ControllerTest extends WebTestCase
{
    public function testInvalidBlank(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/2'); // Has no location yet

        $this->assertResponseStatusCodeSame(200);
        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_postalCode_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_city_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_roadName_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_fromHouseNumber_error')->text());
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#step2_form_toHouseNumber_error')->text());
    }

    public function testAdd(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/2'); // Has no location yet

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('Étape 2 sur 5 Localisation', $crawler->filter('h2')->text());
        $this->assertSame('Étape suivante : Période', $crawler->filter('p.fr-stepper__details')->text());

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form['step2_form[postalCode]'] = '44260';
        $form['step2_form[city]'] = 'Savenay';
        $form['step2_form[roadName]'] = 'Route du Grand Brossais';
        $form['step2_form[fromHouseNumber]'] = '15';
        $form['step2_form[toHouseNumber]'] = '37bis';

        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_3');
    }

    public function testEditUnchanged(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/3ede8b1a-1816-4788-8510-e08f45511cb5/2'); // Already has a location
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();

        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_3');
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
        $crawler = $client->request('GET', '/regulations/form/3ede8b1a-1816-4788-8510-e08f45511cb5/2');

        $this->assertResponseStatusCodeSame(200);
        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form['step2_form[postalCode]'] = $postalCode;
        $form['step2_form[city]'] = 'Savenay';
        $form['step2_form[roadName]'] = 'Route du Grand Brossais';
        $form['step2_form[fromHouseNumber]'] = '15';
        $form['step2_form[toHouseNumber]'] = '37bis';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame("Cette valeur n'est pas valide. Un code postal est composé de 5 chiffres.", $crawler->filter('#step2_form_postalCode_error')->text());
    }

    public function testGeocodingFailure(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/3ede8b1a-1816-4788-8510-e08f45511cb5/2');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form['step2_form[postalCode]'] = '44260';
        $form['step2_form[city]'] = 'Savenay';
        $form['step2_form[roadName]'] = 'Route du GEOCODING_FAILURE';
        $form['step2_form[fromHouseNumber]'] = '15';
        $form['step2_form[toHouseNumber]'] = '37bis';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith("En raison d'un problème technique", $crawler->filter('#step2_form_error')->text());
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

    public function testPrevious(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/form/3ede8b1a-1816-4788-8510-e08f45511cb5/2');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Précédent');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_steps_1', ['uuid' => '3ede8b1a-1816-4788-8510-e08f45511cb5']);
    }

    public function testInvalidFromHouseNumberAndToHouseNumber(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/3ede8b1a-1816-4788-8510-e08f45511cb5/2');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form['step2_form[postalCode]'] = '44260';
        $form['step2_form[city]'] = 'Savenay';
        $form['step2_form[roadName]'] = 'Route du GEOCODING_FAILURE';
        $form['step2_form[fromHouseNumber]'] = 'Plus long que 8 caractères';
        $form['step2_form[toHouseNumber]'] = 'Plus long que 8 caractères';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame("Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.", $crawler->filter('#step2_form_fromHouseNumber_error')->text());
        $this->assertSame("Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.", $crawler->filter('#step2_form_toHouseNumber_error')->text());
    }

    public function testUxEnhancements(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/regulations/form/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/2');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Suivant');
        $this->assertNotNull($saveButton->closest('turbo-frame[id="step-content"][data-turbo-action="advance"][autoscroll]'));
    }
}

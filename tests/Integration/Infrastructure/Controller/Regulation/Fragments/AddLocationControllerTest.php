<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddLocationControllerTest extends AbstractWebTestCase
{
    public function testInvalidBlank(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add'); // Has no location yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Localisation', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_address_error')->text());
    }

    public function testAdd(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add'); // Has no location yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['location_form[address]'] = 'Route du Grand Brossais 44260 Savenay';
        $form['location_form[fromHouseNumber]'] = '15';
        $form['location_form[toHouseNumber]'] = '37bis';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(200);

        $streams = $crawler->filter('turbo-stream');
        $this->assertSame('Route du Grand Brossais', $streams->eq(0)->filter('h3')->text());

        $form = $streams->eq(1)->selectButton('Ajouter une localisation');
        $this->assertSame('http://localhost/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add', $form->getUri());
    }

    public function testGeocodingFailure(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['location_form[address]'] = 'Route du GEOCODING_FAILURE 44260 Savenay';
        $form['location_form[fromHouseNumber]'] = '15';
        $form['location_form[toHouseNumber]'] = '37bis';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith("En raison d'un problème technique", $crawler->filter('#location_form_error')->text());
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/c1beed9a-6ec1-417a-abfd-0b5bd245616b/location/add');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/aaaaaaaa/location/add');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCancel(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/location/add');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Annuler');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulation_location_add_link', ['regulationOrderRecordUuid' => '3ede8b1a-1816-4788-8510-e08f45511cb5']);
    }

    public function testFieldsTooLong(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/3ede8b1a-1816-4788-8510-e08f45511cb5/location/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['location_form[address]'] = str_repeat('a', 256);
        $form['location_form[fromHouseNumber]'] = str_repeat('a', 9);
        $form['location_form[toHouseNumber]'] = str_repeat('a', 9);

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#location_form_address_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.', $crawler->filter('#location_form_fromHouseNumber_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.', $crawler->filter('#location_form_toHouseNumber_error')->text());
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add'); // Has no location yet
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/867d2be6-0d80-41b5-b1ff-8452b30a95f5/location/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

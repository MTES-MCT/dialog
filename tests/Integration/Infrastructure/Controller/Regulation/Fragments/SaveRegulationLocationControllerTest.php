<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class SaveRegulationLocationControllerTest extends AbstractWebTestCase
{
    public function testInvalidBlank(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/_fragment/regulations/location/form/867d2be6-0d80-41b5-b1ff-8452b30a95f5'); // Has no location yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame("Cette valeur ne doit pas être vide.", $crawler->filter('#location_form_address_error')->text());
    }

    public function testAddFullRoad(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/_fragment/regulations/location/form/867d2be6-0d80-41b5-b1ff-8452b30a95f5'); // Has no location yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form['location_form[address]'] = 'Route du Grand Brossais 44260 Savenay';

        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_location', ['uuid' => '867d2be6-0d80-41b5-b1ff-8452b30a95f5']);
    }

    public function testAddRoadSection(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/_fragment/regulations/location/form/867d2be6-0d80-41b5-b1ff-8452b30a95f5'); // Has no location yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form['location_form[address]'] = 'Route du Grand Brossais 44260 Savenay';
        $form['location_form[fromHouseNumber]'] = '15';
        $form['location_form[toHouseNumber]'] = '37bis';

        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_location', ['uuid' => '867d2be6-0d80-41b5-b1ff-8452b30a95f5']);
    }

    public function testEditUnchanged(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/location/form/3ede8b1a-1816-4788-8510-e08f45511cb5'); // Already has a location
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();

        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_location', ['uuid' => '3ede8b1a-1816-4788-8510-e08f45511cb5']);
    }

    public function testGeocodingFailure(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/location/form/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Suivant');
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
        $client->request('GET', '/_fragment/regulations/location/form/c1beed9a-6ec1-417a-abfd-0b5bd245616b');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/location/form/aaaaaaaa');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCancel(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/location/form/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Annuler');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_location', ['uuid' => '3ede8b1a-1816-4788-8510-e08f45511cb5']);
    }

    public function testFieldsTooLong(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/location/form/3ede8b1a-1816-4788-8510-e08f45511cb5');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Suivant');
        $form = $saveButton->form();
        $form['location_form[address]'] = str_repeat('a', 256);
        $form['location_form[fromHouseNumber]'] = str_repeat('a', 9);
        $form['location_form[toHouseNumber]'] = str_repeat('a', 9);

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame("Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.", $crawler->filter('#location_form_address_error')->text());
        $this->assertSame("Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.", $crawler->filter('#location_form_fromHouseNumber_error')->text());
        $this->assertSame("Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.", $crawler->filter('#location_form_toHouseNumber_error')->text());
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/location/form/867d2be6-0d80-41b5-b1ff-8452b30a95f5');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

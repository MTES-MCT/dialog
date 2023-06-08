<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class UpdateLocationControllerTest extends AbstractWebTestCase
{
    public function testInvalidBlank(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Route du Grand Brossais', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['location_form[address]'] = ''; // reset
        $form['location_form[measures][0][type]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_address_error')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_type_error')->text());
        $this->assertStringContainsString('Cette valeur doit être l\'un des choix proposés.', $crawler->filter('#location_form_measures_0_type_error')->text());
    }

    public function testEditAndAddMeasure(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        // Edit
        $values['location_form']['measures'][0]['type'] = 'oneWayTraffic';
        $values['location_form']['measures'][0]['periods'] = []; // Remove period
        // Add
        $values['location_form']['measures'][1]['type'] = 'alternateRoad';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_location', ['uuid' => '51449b82-5032-43c8-a427-46b9ddb44762']);
        $bulletPoints = $crawler->filter('li')->extract(['_text']);
        $this->assertContains('Circulation à sens unique', $bulletPoints);
        $this->assertContains('Circulation alternée', $bulletPoints);
    }

    public function testRemoveMeasure(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/f15ed802-fa9b-4d75-ab04-d62ea46597e9/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        unset($values['location_form']['measures'][0]);

        $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_location', ['uuid' => 'f15ed802-fa9b-4d75-ab04-d62ea46597e9']);
        $this->assertNotContains('Circulation interdite', $crawler->filter('li')->extract(['_text']));
    }

    public function testGeocodingFailure(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
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
        $client->request('GET', '/_fragment/regulations/c1beed9a-6ec1-417a-abfd-0b5bd245616b/location/51449b82-5032-43c8-a427-46b9ddb44762/form');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testLocationNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/c1beed9a-6ec1-417a-abfd-0b5bd245616b/form');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/aaaaa/location/bbbbb/form');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCancel(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);

        $client->submit($crawler->selectButton('Annuler')->form());
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_location', ['uuid' => '51449b82-5032-43c8-a427-46b9ddb44762']);
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

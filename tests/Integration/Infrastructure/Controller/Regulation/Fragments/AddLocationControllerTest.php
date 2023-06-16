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

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['address'] = '';
        $values['location_form']['measures'][0]['type'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_address_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.Cette valeur doit être l\'un des choix proposés.', $crawler->filter('#location_form_measures_0_type_error')->text());
    }

    public function testAdd(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add'); // Has no location yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['address'] = 'Route du Grand Brossais 44260 Savenay';
        $values['location_form']['fromHouseNumber'] = '15';
        $values['location_form']['toHouseNumber'] = '37bis';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['periods'][0]['applicableDays'] = ['monday', 'sunday'];
        $values['location_form']['measures'][0]['periods'][0]['startTime'] = '08:00';
        $values['location_form']['measures'][0]['periods'][0]['endTime'] = '16:00';
        $values['location_form']['measures'][0]['periods'][0]['includeHolidays'] = true;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(200);
        $streams = $crawler->filter('turbo-stream');
        $this->assertSame($streams->first()->attr('target'), 'location_f15ed802-fa9b-4d75-ab04-d62ea46597e9_delete_button');
        $this->assertSame($streams->first()->attr('action'), 'replace');

        $this->assertSame($streams->last()->attr('action'), 'replace');
        $form = $streams->last()->selectButton('Ajouter une localisation');
        $this->assertSame('http://localhost/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add', $form->getUri());
    }

    public function testInvalidBlankPeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add'); // Has no location yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Localisation', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['address'] = 'Route du Grand Brossais 44260 Savenay';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['periods'][0]['applicableDays'] = '';
        $values['location_form']['measures'][0]['periods'][0]['startTime'] = '';
        $values['location_form']['measures'][0]['periods'][0]['endTime'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Le choix sélectionné est invalide.', $crawler->filter('#location_form_measures_0_periods_0_applicableDays_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_periods_0_startTime_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.L\'heure de fin doit être supérieure à l\'heure de début.', $crawler->filter('#location_form_measures_0_periods_0_endTime_error')->text());
    }

    public function testInvalidPeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add'); // Has no location yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Localisation', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Bad period
        $values = $form->getPhpValues();
        $values['location_form']['address'] = 'Route du Grand Brossais 44260 Savenay';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['periods'][0]['startTime'] = '10:00';
        $values['location_form']['measures'][0]['periods'][0]['endTime'] = '08:00';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_periods_0_applicableDays_error')->text());
        $this->assertSame('L\'heure de fin doit être supérieure à l\'heure de début.', $crawler->filter('#location_form_measures_0_periods_0_endTime_error')->text());

        // Bad values
        $values['location_form']['measures'][0]['periods'][0]['applicableDays'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['startTime'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['endTime'] = 'test';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Le choix sélectionné est invalide.', $crawler->filter('#location_form_measures_0_periods_0_applicableDays_error')->text());
        $this->assertSame('Veuillez saisir une heure valide.', $crawler->filter('#location_form_measures_0_periods_0_startTime_error')->text());
        $this->assertSame('Veuillez saisir une heure valide.', $crawler->filter('#location_form_measures_0_periods_0_endTime_error')->text());
    }

    public function testGeocodingFailure(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['address'] = 'Route du GEOCODING_FAILURE 44260 Savenay';
        $values['location_form']['fromHouseNumber'] = '15';
        $values['location_form']['toHouseNumber'] = '37bis';
        $values['location_form']['measures'][0]['type'] = 'noEntry';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue.', $crawler->filter('#location_form_error')->text());
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
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.Veuillez saisir une adresse valide. Ex : Rue Saint-Léonard, 49000 Angers.', $crawler->filter('#location_form_address_error')->text());
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

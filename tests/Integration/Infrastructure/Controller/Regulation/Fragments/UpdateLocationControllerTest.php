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
        $form['location_form[cityCode]'] = ''; // reset
        $form['location_form[cityLabel]'] = ''; // reset
        $form['location_form[roadName]'] = ''; // reset
        $form['location_form[measures][0][type]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_cityLabel_error')->text());
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_type_error')->text());
        $this->assertStringContainsString('Cette valeur doit être l\'un des choix proposés.', $crawler->filter('#location_form_measures_0_type_error')->text());
    }

    public function testWithNegativeMaxSpeed(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['location_form[cityCode]'] = '44195';
        $form['location_form[cityLabel]'] = 'Savenay (44260)';
        $form['location_form[roadName]'] = 'Route du Grand Brossais';
        $form['location_form[measures][0][type]'] = 'speedLimitation';
        $form['location_form[measures][0][maxSpeed]'] = '-10';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur doit être strictement positive.', $crawler->filter('#location_form_measures_0_maxSpeed_error')->text());
    }

    public function testWithoutMaxSpeed(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['location_form[cityCode]'] = '44195';
        $form['location_form[cityLabel]'] = 'Savenay (44260)';
        $form['location_form[roadName]'] = 'Route du Grand Brossais';
        $form['location_form[measures][0][type]'] = 'speedLimitation';
        $form['location_form[measures][0][maxSpeed]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_maxSpeed_error')->text());
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
        $values['location_form']['measures'][0]['type'] = 'speedLimitation';
        $values['location_form']['measures'][0]['maxSpeed'] = 60;
        $values['location_form']['measures'][0]['periods'] = []; // Remove period

        // Add
        $values['location_form']['measures'][1]['type'] = 'alternateRoad';
        $values['location_form']['measures'][1]['vehicleSet']['allVehicles'] = 'yes';
        $values['location_form']['measures'][1]['periods'][0]['recurrenceType'] = 'certainDays';
        $values['location_form']['measures'][1]['periods'][0]['startDate'] = '2023-10-30';
        $values['location_form']['measures'][1]['periods'][0]['startTime']['hour'] = '8';
        $values['location_form']['measures'][1]['periods'][0]['startTime']['minute'] = '0';
        $values['location_form']['measures'][1]['periods'][0]['endDate'] = '2023-10-30';
        $values['location_form']['measures'][1]['periods'][0]['endTime']['hour'] = '16';
        $values['location_form']['measures'][1]['periods'][0]['endTime']['minute'] = '0';
        $values['location_form']['measures'][1]['periods'][0]['dailyRange']['applicableDays'] = ['monday'];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_location', ['uuid' => '51449b82-5032-43c8-a427-46b9ddb44762']);
        $this->assertSame('Vitesse limitée à 60 km/h tous les jours pour tous les véhicules', $crawler->filter('li')->eq(2)->text());
        $this->assertSame('Circulation alternée du 09/06/2023 - 09h00 au 09/06/2023 - 09h00, le lundi pour tous les véhicules', $crawler->filter('li')->eq(3)->text());
    }

    public function testDeletePeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        // Remove period
        $values['location_form']['measures'][0]['periods'] = []; // Remove period

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        $this->assertRouteSame('fragment_regulations_location', ['uuid' => '51449b82-5032-43c8-a427-46b9ddb44762']);
        $this->assertSame('Circulation interdite tous les jours pour tous les véhicules', $crawler->filter('li')->eq(2)->text());
    }

    public function testRemoveDailyRange(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        // Add complete dailyRange
        $values['location_form']['measures'][1]['type'] = 'alternateRoad';
        $values['location_form']['measures'][1]['vehicleSet']['allVehicles'] = 'yes';
        $values['location_form']['measures'][1]['periods'][0]['recurrenceType'] = 'certainDays';
        $values['location_form']['measures'][1]['periods'][0]['startDate'] = '2023-10-30';
        $values['location_form']['measures'][1]['periods'][0]['startTime']['hour'] = '8';
        $values['location_form']['measures'][1]['periods'][0]['startTime']['minute'] = '0';
        $values['location_form']['measures'][1]['periods'][0]['endDate'] = '2023-10-30';
        $values['location_form']['measures'][1]['periods'][0]['endTime']['hour'] = '16';
        $values['location_form']['measures'][1]['periods'][0]['endTime']['minute'] = '0';
        $values['location_form']['measures'][1]['periods'][0]['dailyRange']['applicableDays'] = ['monday'];
        $values['location_form']['measures'][1]['periods'][0]['timeSlots'][0]['startTime']['hour'] = '8';
        $values['location_form']['measures'][1]['periods'][0]['timeSlots'][0]['startTime']['minute'] = '0';
        $values['location_form']['measures'][1]['periods'][0]['timeSlots'][0]['endTime']['hour'] = '18';
        $values['location_form']['measures'][1]['periods'][0]['timeSlots'][0]['endTime']['minute'] = '0';
        $client->request($form->getMethod(), $form->getUri(), $values);
        $crawler = $client->followRedirect();
        $this->assertSame('Circulation alternée du 09/06/2023 - 09h00 au 09/06/2023 - 09h00, le lundi (08h00-18h00) pour tous les véhicules', $crawler->filter('li')->eq(3)->text());

        // Remove added daily range
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['measures'][1]['periods'][0]['recurrenceType'] = 'everyDay';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('Circulation alternée du 09/06/2023 - 09h00 au 09/06/2023 - 09h00 (08h00-18h00) pour tous les véhicules', $crawler->filter('li')->eq(3)->text());
    }

    public function testRemoveTimeSlots(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        // Add complete dailyRange
        $values['location_form']['measures'][1]['type'] = 'alternateRoad';
        $values['location_form']['measures'][1]['vehicleSet']['allVehicles'] = 'yes';
        $values['location_form']['measures'][1]['periods'][0]['recurrenceType'] = 'certainDays';
        $values['location_form']['measures'][1]['periods'][0]['startDate'] = '2023-10-30';
        $values['location_form']['measures'][1]['periods'][0]['startTime']['hour'] = '8';
        $values['location_form']['measures'][1]['periods'][0]['startTime']['minute'] = '0';
        $values['location_form']['measures'][1]['periods'][0]['endDate'] = '2023-10-30';
        $values['location_form']['measures'][1]['periods'][0]['endTime']['hour'] = '16';
        $values['location_form']['measures'][1]['periods'][0]['endTime']['minute'] = '0';
        $values['location_form']['measures'][1]['periods'][0]['dailyRange']['applicableDays'] = ['monday'];
        $values['location_form']['measures'][1]['periods'][0]['timeSlots'][0]['startTime']['hour'] = '8';
        $values['location_form']['measures'][1]['periods'][0]['timeSlots'][0]['startTime']['minute'] = '0';
        $values['location_form']['measures'][1]['periods'][0]['timeSlots'][0]['endTime']['hour'] = '18';
        $values['location_form']['measures'][1]['periods'][0]['timeSlots'][0]['endTime']['minute'] = '0';
        $client->request($form->getMethod(), $form->getUri(), $values);
        $crawler = $client->followRedirect();
        $this->assertSame('Circulation alternée du 09/06/2023 - 09h00 au 09/06/2023 - 09h00, le lundi (08h00-18h00) pour tous les véhicules', $crawler->filter('li')->eq(3)->text());

        // Remove added timeslot
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['measures'][1]['periods'][0]['timeSlots'] = [];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('Circulation alternée du 09/06/2023 - 09h00 au 09/06/2023 - 09h00, le lundi pour tous les véhicules', $crawler->filter('li')->eq(3)->text());
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
        unset($values['location_form']['measures'][1]);

        $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_location', ['uuid' => 'f15ed802-fa9b-4d75-ab04-d62ea46597e9']);
        $this->assertNotContains('Circulation interdite tous les jours pour tous les véhicules', $crawler->filter('li')->extract(['_text']));
    }

    public function testGeocodingFailureHouseNumber(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['location_form[cityCode]'] = '44195';
        $form['location_form[cityLabel]'] = 'Savenay (44260)';
        $form['location_form[roadName]'] = 'Route du GEOCODING_FAILURE';
        $form['location_form[fromHouseNumber]'] = '15';
        $form['location_form[toHouseNumber]'] = '37bis';
    }

    public function testGeocodingFailureFullRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['location_form[address]'] = 'Rue du Parc 59368 La Madeleine';
        $form['location_form[fromHouseNumber]'] = '';
        $form['location_form[toHouseNumber]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue.', $crawler->filter('#location_form_error')->text());
    }

    public function testUpdateAddressFullRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['location_form[address]'] = 'Rue Saint-Victor 59368 La Madeleine';
        $form['location_form[fromHouseNumber]'] = '';
        $form['location_form[toHouseNumber]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(303);
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
        $client->request('GET', '/_fragment/regulations/e413a47e-5928-4353-a8b2-8b7dda27f9a5/location/51449b82-5032-43c8-a427-46b9ddb44762/form');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Annuler');
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

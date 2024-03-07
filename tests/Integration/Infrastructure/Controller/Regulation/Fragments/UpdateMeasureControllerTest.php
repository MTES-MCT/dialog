<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class UpdateMeasureControllerTest extends AbstractWebTestCase
{
    public function testInvalidBlank(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mesure', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['measure_form[type]'] = ''; // reset

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);

        $this->assertSame('Cette valeur ne doit pas être vide. Cette valeur doit être l\'un des choix proposés.', $crawler->filter('#measure_form_type_error')->text());
    }

    public function testWithNegativeMaxSpeed(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['measure_form[type]'] = 'speedLimitation';
        $form['measure_form[maxSpeed]'] = '-10';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur doit être strictement positive.', $crawler->filter('#measure_form_maxSpeed_error')->text());
    }

    public function testWithoutMaxSpeed(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['measure_form[type]'] = 'speedLimitation';
        $form['measure_form[maxSpeed]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_maxSpeed_error')->text());
    }

    public function testAddAndRemoveLocation(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        // Edit measure
        $values['measure_form']['locations'][0] = []; // Remove first

        // Add
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['cityCode'] = '59368';
        $values['measure_form']['locations'][0]['cityLabel'] = 'La Madeleine (59110)';
        $values['measure_form']['locations'][0]['roadName'] = 'Rue Saint-Victor';
        $values['measure_form']['locations'][0]['isEntireStreet'] = '1';
        $values['measure_form']['locations'][0]['fromHouseNumber'] = '3'; // Will be ignored because of isEntireStreet
        $values['measure_form']['locations'][0]['toHouseNumber'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertRouteSame('fragment_regulations_measure', ['uuid' => MeasureFixture::UUID_TYPICAL]);
        $measures = $crawler->filter('[data-testid="measure"]');

        $this->assertSame('Rue Saint-Victor La Madeleine (59110)', $measures->eq(0)->filter('.app-card__content li')->eq(3)->text());
        $this->assertSame('Route du Grand Brossais du n° 15 au n° 37bis Savenay (44260)', $measures->eq(0)->filter('.app-card__content li')->eq(4)->text());
    }

    public function testDeletePeriod(): void
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL);
        $this->assertSame('du 31/10/2023 à 09h00 au 31/10/2023 à 23h00', $crawler->filter('li')->eq(1)->text());

        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['periods'] = []; // Remove period

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        $this->assertRouteSame('fragment_regulations_measure', ['uuid' => MeasureFixture::UUID_TYPICAL]);
        $this->assertSame('tous les jours', $crawler->filter('li')->eq(1)->text());
    }

    public function testRemoveDailyRange(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        // Add complete dailyRange
        $values['measure_form']['periods'][0]['recurrenceType'] = 'certainDays';
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '8';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['endDate'] = '2023-10-30';
        $values['measure_form']['periods'][0]['endTime']['hour'] = '16';
        $values['measure_form']['periods'][0]['endTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['dailyRange']['applicableDays'] = ['monday'];
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['hour'] = '8';
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['hour'] = '18';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['minute'] = '0';
        $client->request($form->getMethod(), $form->getUri(), $values);
        $crawler = $client->followRedirect();
        $this->assertSame('du 09/06/2023 à 10h00 au 09/06/2023 à 10h00, le lundi (08h00-18h00)', $crawler->filter('li')->eq(1)->text());

        // Remove added daily range
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['periods'][0]['recurrenceType'] = 'everyDay';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('du 09/06/2023 à 10h00 au 09/06/2023 à 10h00 (08h00-18h00)', $crawler->filter('li')->eq(1)->text());
    }

    public function testRemoveTimeSlots(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        // Add complete dailyRange
        $values['measure_form']['type'] = 'alternateRoad';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['periods'][0]['recurrenceType'] = 'certainDays';
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '8';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['endDate'] = '2023-10-30';
        $values['measure_form']['periods'][0]['endTime']['hour'] = '16';
        $values['measure_form']['periods'][0]['endTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['dailyRange']['applicableDays'] = ['monday'];
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['hour'] = '8';
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['hour'] = '18';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['minute'] = '0';
        $client->request($form->getMethod(), $form->getUri(), $values);
        $crawler = $client->followRedirect();
        $this->assertSame('du 09/06/2023 à 10h00 au 09/06/2023 à 10h00, le lundi (08h00-18h00)', $crawler->filter('li')->eq(1)->text());

        // Remove added timeslot
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['periods'][0]['timeSlots'] = [];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('du 09/06/2023 à 10h00 au 09/06/2023 à 10h00, le lundi', $crawler->filter('li')->eq(1)->text());
    }

    public function testGeocodingFailureFullRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['measure_form[locations][0][cityCode]'] = '59368';
        $form['measure_form[locations][0][cityLabel]'] = 'La Madeleine (59110)';
        $form['measure_form[locations][0][roadName]'] = 'Rue de NOT_HANDLED_BY_MOCK';
        $form['measure_form[locations][0][fromHouseNumber]'] = '';
        $form['measure_form[locations][0][toHouseNumber]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue. Vérifier le nom de la voie, et les numéros de début et fin.', $crawler->filter('#measure_form_locations_0_roadName_error')->text());
    }

    public function testUpdateAddressFullRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['measure_form[locations][0][cityCode]'] = '59368';
        $form['measure_form[locations][0][cityLabel]'] = 'La Madeleine (59110)';
        $form['measure_form[locations][0][roadName]'] = 'Rue Saint-Victor';
        $form['measure_form[locations][0][isEntireStreet]'] = '1';
        $form['measure_form[locations][0][fromHouseNumber]'] = '';
        $form['measure_form[locations][0][toHouseNumber]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(303);
    }

    public function testUpdateLocationSingleEndSection(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['measure_form[locations][0][cityCode]'] = '82121';
        $form['measure_form[locations][0][cityLabel]'] = 'Montauban (82000)';
        $form['measure_form[locations][0][roadName]'] = 'Rue de la République';
        unset($form['measure_form[locations][0][isEntireStreet]']);
        $form['measure_form[locations][0][fromHouseNumber]'] = '';
        $form['measure_form[locations][0][toHouseNumber]'] = '33';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(303);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testMeasureNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_DOES_NOT_EXIST . '/form');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/aaaaa/measure/bbbbb/form');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCancel(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Annuler');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_measure', ['uuid' => MeasureFixture::UUID_TYPICAL]);
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

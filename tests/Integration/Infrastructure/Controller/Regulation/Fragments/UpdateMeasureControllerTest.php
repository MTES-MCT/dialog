<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\StorageAreaFixture;
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
        $values['measure_form']['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '93070';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Saint-Ouen-sur-Seine';
        $values['measure_form']['locations'][0]['namedStreet']['roadBanId'] = '93070_0074';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Rue Ardoin';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertRouteSame('fragment_regulations_measure', ['uuid' => MeasureFixture::UUID_TYPICAL]);
        $measures = $crawler->filter('[data-testid="measure"]');

        $this->assertSame('Rue Ardoin à Saint-Ouen-sur-Seine', $measures->eq(0)->filter('.app-card__content li')->eq(3)->text());
        $this->assertSame('Rue Eugène Berthoud du n° 47 au n° 65 à Saint-Ouen-sur-Seine', $measures->eq(0)->filter('.app-card__content li')->eq(4)->text());
    }

    public function testRemoveManyLocations(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();

        // Keep only 3rd location
        $loc = $values['measure_form']['locations'][2];
        // Choice is initially empty because it is managed via client-side JS. Need to set it back for the test.
        $loc['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $loc['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';
        $values['measure_form']['locations'] = [$loc];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);

        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertRouteSame('fragment_regulations_measure', ['uuid' => MeasureFixture::UUID_TYPICAL]);
        $measures = $crawler->filter('[data-testid="measure"]');

        $this->assertCount(1, $measures->eq(0)->filter('[data-location-uuid]'));
        $this->assertSame('Avenue Michelet de Allée Isabeau à Avenue Du Cimetière à Saint-Ouen-sur-Seine', $measures->eq(0)->filter('.app-card__content li')->eq(3)->text());
    }

    public function testDeletePeriod(): void
    {
        function ensureRegularSpaces(string $text): string
        {
            // Period text may contain non-breaking spaces
            // Credit: https://stackoverflow.com/a/62082195
            return preg_replace('/\s+/u', ' ', $text);
        }

        $client = $this->login();

        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_CIFS . '/measure/' . MeasureFixture::UUID_CIFS);
        $this->assertSame(
            'du 05/06/2023 à 00h00 au 10/06/2023 à 23h59, du lundi au dimanche (19h00-23h00) du 02/06/2023 à 00h00 au 06/06/2023 à 23h59, le mardi (13h00-15h00 et 20h00-22h00) du 03/06/2023 à 09h00 au 05/06/2023 à 11h00, le mardi et le jeudi (09h00-11h00)',
            ensureRegularSpaces($crawler->filter('li')->eq(1)->text()),
        );

        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_CIFS . '/measure/' . MeasureFixture::UUID_CIFS . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        unset($values['measure_form']['periods'][0]); // Remove period

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);

        $this->assertRouteSame('fragment_regulations_measure', ['uuid' => MeasureFixture::UUID_CIFS]);
        $this->assertSame(
            // 09/06/2023 comes from DateUtilsMock
            'du 02/06/2023 à 00h00 au 06/06/2023 à 23h59, le mardi (13h00-15h00 et 20h00-22h00) du 03/06/2023 à 09h00 au 05/06/2023 à 11h00, le mardi et le jeudi (09h00-11h00)',
            ensureRegularSpaces($crawler->filter('li')->eq(1)->text()),
        );
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
        $this->assertSame('du 30/10/2023 à 08h00 au 30/10/2023 à 16h00, le lundi (08h00-18h00)', $crawler->filter('li')->eq(1)->text());

        // Remove added daily range
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['periods'][0]['recurrenceType'] = 'everyDay';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('du 30/10/2023 à 08h00 au 30/10/2023 à 16h00 (08h00-18h00)', $crawler->filter('li')->eq(1)->text());
    }

    public function testRemoveTimeSlots(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        // Add complete dailyRange
        $values['measure_form']['type'] = 'noEntry';
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
        $this->assertSame('du 30/10/2023 à 08h00 au 30/10/2023 à 16h00, le lundi (08h00-18h00)', $crawler->filter('li')->eq(1)->text());

        // Remove added timeslot
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['periods'][0]['timeSlots'] = [];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('du 30/10/2023 à 08h00 au 30/10/2023 à 16h00, le lundi', $crawler->filter('li')->eq(1)->text());
    }

    public function testGeocodingFailureFullRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['measure_form[locations][0][namedStreet][roadType]'] = 'lane';
        $form['measure_form[locations][0][namedStreet][cityCode]'] = '59368';
        $form['measure_form[locations][0][namedStreet][cityLabel]'] = 'La Madeleine (59110)';
        $form['measure_form[locations][0][namedStreet][roadBanId]'] = '12345_6789';
        $form['measure_form[locations][0][namedStreet][roadName]'] = 'Rue inconnue';
        $form['measure_form[locations][0][namedStreet][fromHouseNumber]'] = '';
        $form['measure_form[locations][0][namedStreet][toHouseNumber]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue. Vérifier le nom de la voie, et les numéros de début et fin.', $crawler->filter('#measure_form_locations_0_namedStreet_roadName_error')->text());
    }

    public function testLaneWithBlankHouseNumbers(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        unset($form['measure_form[locations][1][namedStreet][isEntireStreet]']);
        $form['measure_form[locations][1][namedStreet][fromHouseNumber]'] = '';
        $form['measure_form[locations][1][namedStreet][toHouseNumber]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);

        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_1_namedStreet_fromHouseNumber_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_1_namedStreet_toHouseNumber_error')->text());
    }

    public function testLaneWithUnknownHouseNumbers(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        // Get the raw values.
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        unset($form['measure_form[locations][1][namedStreet][isEntireStreet]']);
        $form['measure_form[locations][1][namedStreet][toHouseNumber]'] = '999'; // Mock will return no result

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('La géolocalisation de la voie entre ces points a échoué', $crawler->filter('#measure_form_locations_1_namedStreet_fromPointType_error')->text());
    }

    public function testUpdateLaneWithIntersections(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][2]['namedStreet']['cityCode'] = '93070';
        $values['measure_form']['locations'][2]['namedStreet']['cityLabel'] = 'Saint-Ouen-sur-Seine';
        $values['measure_form']['locations'][2]['namedStreet']['roadBanId'] = '93070_4185';
        $values['measure_form']['locations'][2]['namedStreet']['roadName'] = 'Rue Des Graviers';
        unset($values['measure_form']['locations'][2]['namedStreet']['isEntireStreet']);
        $values['measure_form']['locations'][2]['namedStreet']['fromRoadBanId'] = '93070_0013';
        $values['measure_form']['locations'][2]['namedStreet']['fromRoadName'] = 'Rue Adrien Lesesne';
        $values['measure_form']['locations'][2]['namedStreet']['toRoadBanId'] = '93070_7170';
        $values['measure_form']['locations'][2]['namedStreet']['toRoadName'] = 'Rue Des Poissonniers';
        $values['measure_form']['locations'][0]['namedStreet']['direction'] = DirectionEnum::BOTH->value;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(303);
    }

    public function testUpdateAddressFullRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        // Inspect existing full road location
        $existingFullRoadLocation = $crawler->filter('[data-testid=measure_form_location_1]');
        $pointsFieldset1 = $existingFullRoadLocation->filter('[aria-labelledby=measure_form_locations_1_namedStreet-points-legend]')->first();
        $this->assertNull($pointsFieldset1->attr('hidden'), 'not_present'); // Attr must be present but its value will be null
        $this->assertNull($pointsFieldset1->attr('disabled'), 'not_present'); // Attr must be present but its value will be null

        // Convert location to full road
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['measure_form[locations][0][namedStreet][roadType]'] = 'lane';
        $form['measure_form[locations][0][namedStreet][cityCode]'] = '93070';
        $form['measure_form[locations][0][namedStreet][cityLabel]'] = 'Saint-Ouen-sur-Seine';
        $form['measure_form[locations][0][namedStreet][roadBanId]'] = '93070_3185';
        $form['measure_form[locations][0][namedStreet][roadName]'] = 'Rue Eugène Berthoud';
        $form['measure_form[locations][0][namedStreet][isEntireStreet]'] = '1';
        $form['measure_form[locations][0][namedStreet][fromHouseNumber]'] = '';
        $form['measure_form[locations][0][namedStreet][toHouseNumber]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(303);
    }

    public function testDepartmentalRoadWithUnknownPointNumbers(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['locations'][0]['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['departmentalRoad']['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['departmentalRoad']['administrator'] = 'Ardèche';
        $values['measure_form']['locations'][0]['departmentalRoad']['roadNumber'] = 'D110';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromPointNumber'] = '6';
        $values['measure_form']['locations'][0]['departmentalRoad']['toPointNumber'] = '15';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromSide'] = 'D';
        $values['measure_form']['locations'][0]['departmentalRoad']['toSide'] = 'D';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromAbscissa'] = 100;
        $values['measure_form']['locations'][0]['departmentalRoad']['toAbscissa'] = 650;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('La géolocalisation de la route entre ces points de repère a échoué', $crawler->filter('#measure_form_locations_0_departmentalRoad_roadNumber_error')->text());
    }

    /*
    public function testChangeDepartmentalRoadToNationalRoadAndBack(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_CIFS . '/measure/' . MeasureFixture::UUID_CIFS . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        // Get the raw values.
        $values = $form->getPhpValues();
        $initialValues = $values;
        $this->assertSame(RoadTypeEnum::DEPARTMENTAL_ROAD->value, $values['measure_form']['locations'][2]['roadType']);
        $values['measure_form']['locations'][2]['roadType'] = RoadTypeEnum::NATIONAL_ROAD->value;
        $values['measure_form']['locations'][2]['nationalRoad']['roadType'] = RoadTypeEnum::NATIONAL_ROAD->value;
        $values['measure_form']['locations'][2]['nationalRoad']['administrator'] = 'DIR Ouest';
        $values['measure_form']['locations'][2]['nationalRoad']['roadNumber'] = 'N176';
        $values['measure_form']['locations'][2]['nationalRoad']['fromPointNumber'] = '1';
        $values['measure_form']['locations'][2]['nationalRoad']['fromSide'] = 'D';
        $values['measure_form']['locations'][2]['nationalRoad']['fromAbscissa'] = 0;
        $values['measure_form']['locations'][2]['nationalRoad']['toPointNumber'] = '2';
        $values['measure_form']['locations'][2]['nationalRoad']['toSide'] = 'D';
        $values['measure_form']['locations'][2]['nationalRoad']['toAbscissa'] = 50;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(303);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $initialValues, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(303);
    }

    public function testNumberedRoadPointNumberZero(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $this->assertSame(RoadTypeEnum::LANE->value, $values['measure_form']['locations'][2]['roadType']);
        $values['measure_form']['locations'][2]['roadType'] = RoadTypeEnum::NATIONAL_ROAD->value;
        $values['measure_form']['locations'][2]['nationalRoad']['roadType'] = RoadTypeEnum::NATIONAL_ROAD->value;
        $values['measure_form']['locations'][2]['nationalRoad']['administrator'] = 'DIR Ouest';
        $values['measure_form']['locations'][2]['nationalRoad']['roadNumber'] = 'N12';
        $values['measure_form']['locations'][2]['nationalRoad']['fromPointNumber'] = '0';
        $values['measure_form']['locations'][2]['nationalRoad']['fromSide'] = 'U';
        $values['measure_form']['locations'][2]['nationalRoad']['fromAbscissa'] = 0;
        $values['measure_form']['locations'][2]['nationalRoad']['toPointNumber'] = '1';
        $values['measure_form']['locations'][2]['nationalRoad']['toSide'] = 'U';
        $values['measure_form']['locations'][2]['nationalRoad']['toAbscissa'] = 0;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(303);
        $crawler = $client->followRedirect();
        $this->assertSame('N12 (DIR Ouest) du PR 0+0 (côté U) au PR 1+0 (côté U)', $crawler->filter('[data-location-uuid]')->eq(3)->text());
    }*/

    public function testNationalRoadWinterMaintenanceSetAndClearStorageArea(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_WINTER_MAINTENANCE . '/measure/' . MeasureFixture::UUID_WINTER_MAINTENANCE . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $this->assertSame(RoadTypeEnum::NATIONAL_ROAD->value, $values['measure_form']['locations'][0]['roadType']);
        $choices = $crawler->filter('select[name="measure_form[locations][0][nationalRoad][storageArea]"] > option')->each(fn ($node) => [$node->attr('value'), $node->text()]);

        $this->assertEquals([
            ['', 'Sélectionner une aire de stockage'],
            [StorageAreaFixture::UUID_DIRO_N176, 'Zone de stockage 18-22 N176 Voie de droite'],
        ], $choices);
        $values['measure_form']['locations'][0]['nationalRoad']['storageArea'] = StorageAreaFixture::UUID_DIRO_N176;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(303);

        $values['measure_form']['locations'][0]['nationalRoad']['storageArea'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(303);
    }

    public function testNationalRoadWinterMaintenanceInvalidStorageArea(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_WINTER_MAINTENANCE . '/measure/' . MeasureFixture::UUID_WINTER_MAINTENANCE . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $this->assertSame(RoadTypeEnum::NATIONAL_ROAD->value, $values['measure_form']['locations'][0]['roadType']);
        $values['measure_form']['locations'][0]['nationalRoad']['storageArea'] = '8d32e8c4-ee98-4183-aea1-b03d341d971d';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Le choix sélectionné est invalide.', $crawler->filter('#measure_form_locations_0_nationalRoad_storageArea_error')->text());
    }

    public function testEditAsUserRawGeoJSONHidden(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $rawGeoJSONOption = $crawler->filter('#measure_form_locations_0_roadType')->filter('option')->eq(4);
        $this->assertSame('Données brutes GeoJSON', $rawGeoJSONOption->innerText());
        $this->assertSame('', $rawGeoJSONOption->attr('hidden'));
        $this->assertSame('disabled', $rawGeoJSONOption->attr('disabled'));
    }

    public function testEditAsAdminRawGeoJSONShown(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $rawGeoJSONOption = $crawler->filter('#measure_form_locations_0_roadType')->filter('option')->eq(4);
        $this->assertSame('Données brutes GeoJSON', $rawGeoJSONOption->innerText());
        $this->assertSame(null, $rawGeoJSONOption->attr('hidden'));
        $this->assertSame(null, $rawGeoJSONOption->attr('disabled'));
    }

    public function testEditExistingRawGeoJSONAsUser(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/' . MeasureFixture::UUID_RAWGEOJSON . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['measure_form[locations][0][rawGeoJSON][label]'] = 'New label';
        $form['measure_form[locations][0][rawGeoJSON][geometry]'] = '{"type": "Point", "coordinates": [2.346603289433233, 48.90376697673585]}';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(303);
    }

    public function testReplaceRawGeoJSONWithLane(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/' . MeasureFixture::UUID_RAWGEOJSON . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['rawGeoJSON']['label'] = ''; // Simulate effect of changing road type: old fields become disabled and submitted as empty
        $values['measure_form']['locations'][0]['rawGeoJSON']['geometry'] = '';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '93070';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Saint-Ouen-sur-Seine';
        $values['measure_form']['locations'][0]['namedStreet']['roadBanId'] = '93070_3185';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Rue Eugène Berthoud';
        $values['measure_form']['locations'][0]['namedStreet']['isEntireStreet'] = '1';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(303);
    }

    private function provideTestEditRawGeoJSONWithInvalidJSON(): array
    {
        return [
            'invalid-json' => ['geometry' => '{"fuoiu}'],
            'invalid-geojson' => ['geometry' => '{"type": "Point"}'], // JSON valide, mais pas conforme à la spec GeoJSON
        ];
    }

    /**
     * @dataProvider provideTestEditRawGeoJSONWithInvalidJSON
     */
    public function testEditRawGeoJSONWithInvalidJSON(string $geometry): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/' . MeasureFixture::UUID_RAWGEOJSON . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['locations'][0]['roadType'] = 'rawGeoJSON';
        $values['measure_form']['locations'][0]['rawGeoJSON']['label'] = 'Invalide';
        $values['measure_form']['locations'][0]['rawGeoJSON']['geometry'] = $geometry;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette valeur doit être une géométrie GeoJSON valide', $crawler->filter('#measure_form_locations_0_rawGeoJSON_geometry_error')->text());
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

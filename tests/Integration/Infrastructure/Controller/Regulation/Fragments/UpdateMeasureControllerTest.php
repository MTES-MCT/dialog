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
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mesure', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form[$formName . '[type]'] = ''; // reset

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);

        $this->assertSame('Cette valeur ne doit pas être vide. Cette valeur doit être l\'un des choix proposés.', $crawler->filter('#' . $formName . '_type_error')->text());
    }

    public function testWithNegativeMaxSpeed(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form[$formName . '[type]'] = 'speedLimitation';
        $form[$formName . '[maxSpeed]'] = '-10';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur doit être strictement positive.', $crawler->filter('#' . $formName . '_maxSpeed_error')->text());
    }

    public function testWithMaxSpeedTooHigh(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form[$formName . '[type]'] = 'speedLimitation';
        $form[$formName . '[maxSpeed]'] = '150';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur doit être inférieure ou égale à 130.', $crawler->filter('#' . $formName . '_maxSpeed_error')->text());
    }

    public function testWithoutMaxSpeed(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form[$formName . '[type]'] = 'speedLimitation';
        $form[$formName . '[maxSpeed]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#' . $formName . '_maxSpeed_error')->text());
    }

    public function testAddAndRemoveLocation(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        // Edit measure
        $values[$formName]['locations'][0] = []; // Remove first
        // Road name is initially empty because its choices are managed via client-side JS. Need to set it back for the test.
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';

        // Add
        $values[$formName]['locations'][0]['roadType'] = 'lane';
        $values[$formName]['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values[$formName]['locations'][0]['namedStreet']['cityCode'] = '93070';
        $values[$formName]['locations'][0]['namedStreet']['cityLabel'] = 'Saint-Ouen-sur-Seine';
        $values[$formName]['locations'][0]['namedStreet']['roadBanId'] = '93070_0074';
        $values[$formName]['locations'][0]['namedStreet']['roadName'] = 'Rue Ardoin';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);

        $this->assertResponseStatusCodeSame(200);
        $measures = $crawler->filter('[data-testid="measure"]');

        $this->assertSame('Rue Ardoin à Saint-Ouen-sur-Seine', $measures->eq(0)->filter('.app-card__content li')->eq(3)->text());
        $this->assertSame('Rue Eugène Berthoud du n° 47 au n° 65 à Saint-Ouen-sur-Seine', $measures->eq(0)->filter('.app-card__content li')->eq(4)->text());
    }

    public function testRemoveManyLocations(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();

        // Road name is initially empty because its choices are managed via client-side JS. Need to set it back for the test.
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';

        // Keep only 3rd location
        $values[$formName]['locations'] = [$values[$formName]['locations'][2]];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);

        $this->assertResponseStatusCodeSame(200);
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

        $formName = 'measure_form_' . MeasureFixture::UUID_CIFS;

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
        unset($values[$formName]['periods'][0]); // Remove period

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(200);

        $this->assertSame(
            // 09/06/2023 comes from DateUtilsMock
            'du 02/06/2023 à 00h00 au 06/06/2023 à 23h59, le mardi (13h00-15h00 et 20h00-22h00) du 03/06/2023 à 09h00 au 05/06/2023 à 11h00, le mardi et le jeudi (09h00-11h00)',
            ensureRegularSpaces($crawler->filter('li')->eq(1)->text()),
        );
    }

    public function testRemoveDailyRange(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        // Add complete dailyRange
        $values[$formName]['periods'][0]['recurrenceType'] = 'certainDays';
        $values[$formName]['periods'][0]['startDate'] = '2023-10-30';
        $values[$formName]['periods'][0]['startTime']['hour'] = '8';
        $values[$formName]['periods'][0]['startTime']['minute'] = '0';
        $values[$formName]['periods'][0]['endDate'] = '2023-10-30';
        $values[$formName]['periods'][0]['endTime']['hour'] = '16';
        $values[$formName]['periods'][0]['endTime']['minute'] = '0';
        $values[$formName]['periods'][0]['dailyRange']['applicableDays'] = ['monday'];
        $values[$formName]['periods'][0]['timeSlots'][0]['startTime']['hour'] = '8';
        $values[$formName]['periods'][0]['timeSlots'][0]['startTime']['minute'] = '0';
        $values[$formName]['periods'][0]['timeSlots'][0]['endTime']['hour'] = '18';
        $values[$formName]['periods'][0]['timeSlots'][0]['endTime']['minute'] = '0';
        // Road name is initially empty because its choices are managed via client-side JS. Need to set it back for the test.
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('du 30/10/2023 à 08h00 au 30/10/2023 à 16h00, le lundi (08h00-18h00)', $crawler->filter('li')->eq(1)->text());

        // Remove added daily range
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values[$formName]['periods'][0]['recurrenceType'] = 'everyDay';
        // Road name is initially empty because its choices are managed via client-side JS. Need to set it back for the test.
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('du 30/10/2023 à 08h00 au 30/10/2023 à 16h00 (08h00-18h00)', $crawler->filter('li')->eq(1)->text());
    }

    public function testRemoveTimeSlots(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        // Add complete dailyRange
        $values[$formName]['type'] = 'noEntry';
        $values[$formName]['vehicleSet']['allVehicles'] = 'yes';
        $values[$formName]['periods'][0]['recurrenceType'] = 'certainDays';
        $values[$formName]['periods'][0]['startDate'] = '2023-10-30';
        $values[$formName]['periods'][0]['startTime']['hour'] = '8';
        $values[$formName]['periods'][0]['startTime']['minute'] = '0';
        $values[$formName]['periods'][0]['endDate'] = '2023-10-30';
        $values[$formName]['periods'][0]['endTime']['hour'] = '16';
        $values[$formName]['periods'][0]['endTime']['minute'] = '0';
        $values[$formName]['periods'][0]['dailyRange']['applicableDays'] = ['monday'];
        $values[$formName]['periods'][0]['timeSlots'][0]['startTime']['hour'] = '8';
        $values[$formName]['periods'][0]['timeSlots'][0]['startTime']['minute'] = '0';
        $values[$formName]['periods'][0]['timeSlots'][0]['endTime']['hour'] = '18';
        $values[$formName]['periods'][0]['timeSlots'][0]['endTime']['minute'] = '0';
        // Road name is initially empty because its choices are managed via client-side JS. Need to set it back for the test.
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertSame('du 30/10/2023 à 08h00 au 30/10/2023 à 16h00, le lundi (08h00-18h00)', $crawler->filter('li')->eq(1)->text());

        // Remove added timeslot
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values[$formName]['periods'][0]['timeSlots'] = [];
        // Road name is initially empty because its choices are managed via client-side JS. Need to set it back for the test.
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('du 30/10/2023 à 08h00 au 30/10/2023 à 16h00, le lundi', $crawler->filter('li')->eq(1)->text());
    }

    public function testGeocodingFailureFullRoad(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form[$formName . '[locations][0][namedStreet][roadType]'] = 'lane';
        $form[$formName . '[locations][0][namedStreet][cityCode]'] = '59368';
        $form[$formName . '[locations][0][namedStreet][cityLabel]'] = 'La Madeleine (59110)';
        $form[$formName . '[locations][0][namedStreet][roadBanId]'] = '12345_6789';
        $form[$formName . '[locations][0][namedStreet][roadName]'] = 'Rue inconnue';
        $form[$formName . '[locations][0][namedStreet][fromHouseNumber]'] = '';
        $form[$formName . '[locations][0][namedStreet][toHouseNumber]'] = '';
        // Road name is initially empty because its choices are managed via client-side JS. Need to set it back for the test.
        $values = $form->getPhpValues();
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue. Vérifier le nom de la voie, et les numéros de début et fin.', $crawler->filter('#' . $formName . '_locations_0_namedStreet_roadName_error')->text());
    }

    public function testLaneWithBlankHouseNumbers(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        unset($form[$formName . '[locations][1][namedStreet][isEntireStreet]']);
        $form[$formName . '[locations][1][namedStreet][fromHouseNumber]'] = '';
        $form[$formName . '[locations][1][namedStreet][toHouseNumber]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);

        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#' . $formName . '_locations_1_namedStreet_fromHouseNumber_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#' . $formName . '_locations_1_namedStreet_toHouseNumber_error')->text());
    }

    public function testUpdateLaneWithIntersections(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values[$formName]['locations'][2]['namedStreet']['cityCode'] = '93070';
        $values[$formName]['locations'][2]['namedStreet']['cityLabel'] = 'Saint-Ouen-sur-Seine';
        $values[$formName]['locations'][2]['namedStreet']['roadBanId'] = '93070_4185';
        $values[$formName]['locations'][2]['namedStreet']['roadName'] = 'Rue Des Graviers';
        unset($values[$formName]['locations'][2]['namedStreet']['isEntireStreet']);
        $values[$formName]['locations'][2]['namedStreet']['fromRoadBanId'] = '93070_0013';
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Rue Adrien Lesesne';
        $values[$formName]['locations'][2]['namedStreet']['toRoadBanId'] = '93070_7170';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Rue Des Poissonniers';
        $values[$formName]['locations'][0]['namedStreet']['direction'] = DirectionEnum::BOTH->value;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testUpdateAddressFullRoad(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        // Inspect existing full road location
        $existingFullRoadLocation = $crawler->filter('[data-testid=measure_form_location_1]');
        $pointsFieldset1 = $existingFullRoadLocation->filter('[aria-labelledby=' . $formName . '_locations_1_namedStreet-points-legend]')->first();
        $this->assertNull($pointsFieldset1->attr('hidden'), 'not_present'); // Attr must be present but its value will be null
        $this->assertNull($pointsFieldset1->attr('disabled'), 'not_present'); // Attr must be present but its value will be null

        // Convert location to full road
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form[$formName . '[locations][0][namedStreet][roadType]'] = 'lane';
        $form[$formName . '[locations][0][namedStreet][cityCode]'] = '93070';
        $form[$formName . '[locations][0][namedStreet][cityLabel]'] = 'Saint-Ouen-sur-Seine';
        $form[$formName . '[locations][0][namedStreet][roadBanId]'] = '93070_3185';
        $form[$formName . '[locations][0][namedStreet][roadName]'] = 'Rue Eugène Berthoud';
        $form[$formName . '[locations][0][namedStreet][isEntireStreet]'] = '1';
        $form[$formName . '[locations][0][namedStreet][fromHouseNumber]'] = '';
        $form[$formName . '[locations][0][namedStreet][toHouseNumber]'] = '';
        // Road name is initially empty because its choices are managed via client-side JS. Need to set it back for the test.
        $values = $form->getPhpValues();
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testDepartmentalRoadWithUnknownPointNumbers(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values[$formName]['type'] = 'noEntry';
        $values[$formName]['vehicleSet']['allVehicles'] = 'yes';
        $values[$formName]['locations'][0]['namedStreet'] = [];
        $values[$formName]['locations'][0]['nationalRoad'] = [];
        $values[$formName]['locations'][0]['rawGeoJSON'] = [];
        $values[$formName]['locations'][0]['roadType'] = 'departmentalRoad';
        $values[$formName]['locations'][0]['departmentalRoad']['roadType'] = 'departmentalRoad';
        $values[$formName]['locations'][0]['departmentalRoad']['administrator'] = 'Ardèche';
        $values[$formName]['locations'][0]['departmentalRoad']['roadNumber'] = 'D110';
        $values[$formName]['locations'][0]['departmentalRoad']['fromPointNumber'] = '6';
        $values[$formName]['locations'][0]['departmentalRoad']['toPointNumber'] = '15';
        $values[$formName]['locations'][0]['departmentalRoad']['fromSide'] = 'D';
        $values[$formName]['locations'][0]['departmentalRoad']['toSide'] = 'D';
        $values[$formName]['locations'][0]['departmentalRoad']['fromAbscissa'] = 100;
        $values[$formName]['locations'][0]['departmentalRoad']['toAbscissa'] = 650;
        // Road name is initially empty because its choices are managed via client-side JS. Need to set it back for the test.
        $values[$formName]['locations'][2]['namedStreet']['fromRoadName'] = 'Allée Isabeau';
        $values[$formName]['locations'][2]['namedStreet']['toRoadName'] = 'Avenue Du Cimetière';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('La géolocalisation de la route entre ces points de repère a échoué', $crawler->filter('#' . $formName . '_locations_0_departmentalRoad_roadNumber_error')->text());
    }

    /*
    public function testChangeDepartmentalRoadToNationalRoadAndBack(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_CIFS;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_CIFS . '/measure/' . MeasureFixture::UUID_CIFS . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        // Get the raw values.
        $values = $form->getPhpValues();
        $initialValues = $values;
        $this->assertSame(RoadTypeEnum::DEPARTMENTAL_ROAD->value, $values[$formName]['locations'][2]['roadType']);
        $values[$formName]['locations'][2]['roadType'] = RoadTypeEnum::NATIONAL_ROAD->value;
        $values[$formName]['locations'][2]['nationalRoad']['roadType'] = RoadTypeEnum::NATIONAL_ROAD->value;
        $values[$formName]['locations'][2]['nationalRoad']['administrator'] = 'DIR Ouest';
        $values[$formName]['locations'][2]['nationalRoad']['roadNumber'] = 'N176';
        $values[$formName]['locations'][2]['nationalRoad']['fromPointNumber'] = '1';
        $values[$formName]['locations'][2]['nationalRoad']['fromSide'] = 'D';
        $values[$formName]['locations'][2]['nationalRoad']['fromAbscissa'] = 0;
        $values[$formName]['locations'][2]['nationalRoad']['toPointNumber'] = '2';
        $values[$formName]['locations'][2]['nationalRoad']['toSide'] = 'D';
        $values[$formName]['locations'][2]['nationalRoad']['toAbscissa'] = 50;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(200);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $initialValues, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testNumberedRoadPointNumberZero(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $this->assertSame(RoadTypeEnum::LANE->value, $values[$formName]['locations'][2]['roadType']);
        $values[$formName]['locations'][2]['roadType'] = RoadTypeEnum::NATIONAL_ROAD->value;
        $values[$formName]['locations'][2]['nationalRoad']['roadType'] = RoadTypeEnum::NATIONAL_ROAD->value;
        $values[$formName]['locations'][2]['nationalRoad']['administrator'] = 'DIR Ouest';
        $values[$formName]['locations'][2]['nationalRoad']['roadNumber'] = 'N12';
        $values[$formName]['locations'][2]['nationalRoad']['fromPointNumber'] = '0';
        $values[$formName]['locations'][2]['nationalRoad']['fromSide'] = 'U';
        $values[$formName]['locations'][2]['nationalRoad']['fromAbscissa'] = 0;
        $values[$formName]['locations'][2]['nationalRoad']['toPointNumber'] = '1';
        $values[$formName]['locations'][2]['nationalRoad']['toSide'] = 'U';
        $values[$formName]['locations'][2]['nationalRoad']['toAbscissa'] = 0;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('N12 (DIR Ouest) du PR 0+0 (côté U) au PR 1+0 (côté U)', $crawler->filter('[data-location-uuid]')->eq(3)->text());
    }*/

    public function testNationalRoadWinterMaintenanceSetAndClearStorageArea(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_WINTER_MAINTENANCE;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_WINTER_MAINTENANCE . '/measure/' . MeasureFixture::UUID_WINTER_MAINTENANCE . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $this->assertSame(RoadTypeEnum::NATIONAL_ROAD->value, $values[$formName]['locations'][0]['roadType']);
        $choices = $crawler->filter('select[name="' . $formName . '[locations][0][nationalRoad][storageArea]"] > option')->each(fn ($node) => [$node->attr('value'), $node->text()]);

        $this->assertEquals([
            ['', 'Sélectionner une aire de stockage'],
            [StorageAreaFixture::UUID_DIRO_N176, 'Zone de stockage 18-22 N176 Voie de droite'],
        ], $choices);
        $values[$formName]['locations'][0]['nationalRoad']['storageArea'] = StorageAreaFixture::UUID_DIRO_N176;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(200);

        $values[$formName]['locations'][0]['nationalRoad']['storageArea'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(200);
    }

    public function testNationalRoadWinterMaintenanceInvalidStorageArea(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_WINTER_MAINTENANCE;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_WINTER_MAINTENANCE . '/measure/' . MeasureFixture::UUID_WINTER_MAINTENANCE . '/form');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $this->assertSame(RoadTypeEnum::NATIONAL_ROAD->value, $values[$formName]['locations'][0]['roadType']);
        $values[$formName]['locations'][0]['nationalRoad']['storageArea'] = '8d32e8c4-ee98-4183-aea1-b03d341d971d';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Le choix sélectionné est invalide.', $crawler->filter('#' . $formName . '_locations_0_nationalRoad_storageArea_error')->text());
    }

    public function testEditAsUserRawGeoJSONShown(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $rawGeoJSONOption = $crawler->filter('#' . $formName . '_locations_0_roadType')->filter('option')->eq(4);
        $this->assertSame('Tracé de linéaire (voie)', $rawGeoJSONOption->innerText());
        $this->assertSame(null, $rawGeoJSONOption->attr('hidden'));
        $this->assertSame(null, $rawGeoJSONOption->attr('disabled'));
    }

    public function testEditAsAdminRawGeoJSONShown(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $formName = 'measure_form_' . MeasureFixture::UUID_TYPICAL;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $rawGeoJSONOption = $crawler->filter('#' . $formName . '_locations_0_roadType')->filter('option')->eq(4);
        $this->assertSame('Tracé de linéaire (voie)', $rawGeoJSONOption->innerText());
        $this->assertSame(null, $rawGeoJSONOption->attr('hidden'));
        $this->assertSame(null, $rawGeoJSONOption->attr('disabled'));
    }

    public function testEditExistingRawGeoJSONAsUser(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_RAWGEOJSON;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/' . MeasureFixture::UUID_RAWGEOJSON . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        // La carte de dessin est zoomée sur l'organisation, et la localisation étant
        // déjà enregistrée, le bouton de tracé propose l'état "Modifier le tracé"
        $drawMap = $crawler->filter('[data-controller="draw-line-map"]')->first();
        $bbox = json_decode($drawMap->attr('data-draw-line-map-org-bbox-json-value'), true);
        $this->assertCount(4, $bbox);
        $this->assertSame('true', $drawMap->attr('data-draw-line-map-persisted-value'));

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form[$formName . '[locations][0][rawGeoJSON][label]'] = 'New label';
        $form[$formName . '[locations][0][rawGeoJSON][geometry]'] = '{"type": "Point", "coordinates": [2.346603289433233, 48.90376697673585]}';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(200);
    }

    public function testReplaceRawGeoJSONWithLane(): void
    {
        $client = $this->login();
        $formName = 'measure_form_' . MeasureFixture::UUID_RAWGEOJSON;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/' . MeasureFixture::UUID_RAWGEOJSON . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values[$formName]['locations'][0]['roadType'] = 'lane';
        $values[$formName]['locations'][0]['rawGeoJSON']['label'] = ''; // Simulate effect of changing road type: old fields become disabled and submitted as empty
        $values[$formName]['locations'][0]['rawGeoJSON']['geometry'] = '';
        $values[$formName]['locations'][0]['namedStreet']['cityCode'] = '93070';
        $values[$formName]['locations'][0]['namedStreet']['cityLabel'] = 'Saint-Ouen-sur-Seine';
        $values[$formName]['locations'][0]['namedStreet']['roadBanId'] = '93070_3185';
        $values[$formName]['locations'][0]['namedStreet']['roadName'] = 'Rue Eugène Berthoud';
        $values[$formName]['locations'][0]['namedStreet']['isEntireStreet'] = '1';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(200);
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
        $formName = 'measure_form_' . MeasureFixture::UUID_RAWGEOJSON;
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/' . MeasureFixture::UUID_RAWGEOJSON . '/form');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values[$formName]['type'] = 'noEntry';
        $values[$formName]['vehicleSet']['allVehicles'] = 'yes';
        $values[$formName]['locations'][0]['roadType'] = 'rawGeoJSON';
        $values[$formName]['locations'][0]['rawGeoJSON']['label'] = 'Invalide';
        $values[$formName]['locations'][0]['rawGeoJSON']['geometry'] = $geometry;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette valeur doit être une géométrie GeoJSON valide', $crawler->filter('#' . $formName . '_locations_0_rawGeoJSON_geometry_error')->text());
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

    public function testHtmlIdsDoNotCollideWithAddMeasureForm(): void
    {
        // Un formulaire de modification et le formulaire d'ajout peuvent être ouverts en même temps
        // sur la page d'un arrêté : leurs identifiants HTML ne doivent pas se recouper, sinon les labels
        // et le bouton « Valider » du second agissent sur le premier (#2131).
        $client = $this->login();

        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/form');
        $this->assertResponseStatusCodeSame(200);
        $updateForm = $crawler->filter('form')->first();
        $this->assertSame('measure_form_' . MeasureFixture::UUID_TYPICAL, $updateForm->attr('name'));
        $this->assertSame('measure_form_' . MeasureFixture::UUID_TYPICAL, $updateForm->attr('id'));
        $this->assertSame($updateForm->attr('id'), $crawler->selectButton('Valider')->attr('form'));
        $updateIds = $crawler->filter('[id]')->extract(['id']);

        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $addForm = $crawler->filter('form')->first();
        $this->assertSame('measure_form', $addForm->attr('name'));
        $this->assertSame('measure_form', $addForm->attr('id'));
        $this->assertSame($addForm->attr('id'), $crawler->selectButton('Valider')->attr('form'));
        $addIds = $crawler->filter('[id]')->extract(['id']);

        $this->assertNotEmpty($updateIds);
        $this->assertNotEmpty($addIds);
        $this->assertSame([], array_values(array_intersect($addIds, $updateIds)));
    }
}

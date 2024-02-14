<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddMeasureControllerTest extends AbstractWebTestCase
{
    public function testInvalidBlank(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Localisation', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['cityCode'] = '';
        $values['location_form']['cityLabel'] = '';
        $values['location_form']['measures'][0]['type'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_cityLabel_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide. Cette valeur doit être l\'un des choix proposés.', $crawler->filter('#location_form_measures_0_type_error')->text());
    }

    public function testInvalidBlankDepartmentalRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add?feature_road_type=true');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Localisation', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['roadType'] = 'departmentalRoad';
        $values['location_form']['administrator'] = '';
        $values['location_form']['roadNumber'] = '';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'yes';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles(), ['feature_road_type' => true]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_administrator_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_roadNumber_error')->text());
    }

    public function testCertainDaysWithoutApplicableDays(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add?feature_road_type=true');

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['measures'][0]['periods'][0]['recurrenceType'] = 'certainDays';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles(), ['feature_road_type' => true]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_periods_0_dailyRange_applicableDays_error')->text());
    }

    public function testAdd(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['cityCode'] = '44195';
        $values['location_form']['cityLabel'] = 'Savenay (44260)';
        $values['location_form']['roadName'] = 'Route du Grand Brossais';
        $values['location_form']['fromHouseNumber'] = '15';
        $values['location_form']['toHouseNumber'] = '37bis';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'yes';
        $values['location_form']['measures'][0]['vehicleSet']['restrictedTypes'] = ['heavyGoodsVehicle', 'dimensions', 'other'];
        $values['location_form']['measures'][0]['vehicleSet']['otherRestrictedTypeText'] = 'Matières dangereuses';
        $values['location_form']['measures'][0]['vehicleSet']['exemptedTypes'] = ['commercial', 'other'];
        $values['location_form']['measures'][0]['vehicleSet']['otherExemptedTypeText'] = 'Déchets industriels';
        $values['location_form']['measures'][0]['vehicleSet']['heavyweightMaxWeight'] = 3.5;
        $values['location_form']['measures'][0]['vehicleSet']['maxWidth'] = 0.0; // Zero OK
        $values['location_form']['measures'][0]['vehicleSet']['maxLength'] = -0; // Zero OK
        $values['location_form']['measures'][0]['vehicleSet']['maxHeight'] = '2.50';
        $values['location_form']['measures'][0]['periods'][0]['recurrenceType'] = 'certainDays';
        $values['location_form']['measures'][0]['periods'][0]['startDate'] = '2023-10-30';
        $values['location_form']['measures'][0]['periods'][0]['startTime']['hour'] = '8';
        $values['location_form']['measures'][0]['periods'][0]['startTime']['minute'] = '0';
        $values['location_form']['measures'][0]['periods'][0]['endDate'] = '2023-10-30';
        $values['location_form']['measures'][0]['periods'][0]['endTime']['hour'] = '16';
        $values['location_form']['measures'][0]['periods'][0]['endTime']['minute'] = '0';
        $values['location_form']['measures'][0]['periods'][0]['dailyRange']['applicableDays'] = ['monday'];
        $values['location_form']['measures'][0]['periods'][0]['timeSlots'][0]['startTime']['hour'] = '8';
        $values['location_form']['measures'][0]['periods'][0]['timeSlots'][0]['startTime']['minute'] = '0';
        $values['location_form']['measures'][0]['periods'][0]['timeSlots'][0]['endTime']['hour'] = '20';
        $values['location_form']['measures'][0]['periods'][0]['timeSlots'][0]['endTime']['minute'] = '0';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(200);

        $streams = $crawler->filter('turbo-stream')->extract(['action', 'target']);
        $this->assertEquals([
            ['replace', 'location_' . LocationFixture::UUID_PERMANENT_ONLY_ONE . '_delete_button'],
            ['append', 'location_list'],
            ['replace', 'block_location'],
            ['replace', 'block_export'],
            ['update', 'block_publication'],
        ], $streams);

        $addLocationBtn = $crawler->filter('turbo-stream[target=block_location]')->selectButton('Ajouter une localisation');
        $this->assertSame('http://localhost/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/location/add', $addLocationBtn->form()->getUri());
    }

    public function testAddDepartmentalRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/location/add?feature_road_type=true');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['roadType'] = 'departmentalRoad';
        $values['location_form']['administrator'] = 'Ain';
        $values['location_form']['roadNumber'] = 'D1075';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'yes';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles(), ['feature_road_type' => true]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSame('D1075 (Ain)', $crawler->filter('h3')->text());
    }

    public function testInvalidVehicleSetBlankRestrictedTypes(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'no';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Veuillez spécifier un ou plusieurs véhicules concernés.', $crawler->filter('#location_form_measures_0_vehicleSet_restrictedTypes_error')->text());
    }

    public function testInvalidVehicleSetBlankOtherRestrictedTypeText(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'no';
        $values['location_form']['measures'][0]['vehicleSet']['restrictedTypes'] = ['other'];
        $values['location_form']['measures'][0]['vehicleSet']['otherRestrictedTypeText'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_vehicleSet_otherRestrictedTypeText_error')->text());
    }

    public function testInvalidVehicleSetBlankOtherExemptedTypeText(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'yes';
        $values['location_form']['measures'][0]['vehicleSet']['exemptedTypes'] = ['other'];
        $values['location_form']['measures'][0]['vehicleSet']['otherExemptedTypeText'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_vehicleSet_otherExemptedTypeText_error')->text());
    }

    public function testBlankCritairTypes(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['cityCode'] = '44195';
        $values['location_form']['cityLabel'] = 'Savenay (44260)';
        $values['location_form']['roadName'] = 'Route du Grand Brossais';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['restrictedTypes'] = ['critair'];
        $values['location_form']['measures'][0]['vehicleSet']['critairTypes'] = [];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_vehicleSet_critairTypes_error')->text());
    }

    public function testInvalidCritairTypes(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['cityCode'] = '44195';
        $values['location_form']['cityLabel'] = 'Savenay (44260)';
        $values['location_form']['roadName'] = 'Route du Grand Brossais';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['restrictedTypes'] = ['critair'];
        $values['location_form']['measures'][0]['vehicleSet']['critairTypes'] = ['invalidCritair'];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Le choix sélectionné est invalide.', $crawler->filter('#location_form_measures_0_vehicleSet_critairTypes_error')->text());
    }

    public function testInvalidVehicleSetOtherTextsTooLong(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'no';
        $values['location_form']['measures'][0]['vehicleSet']['restrictedTypes'] = ['other'];
        $values['location_form']['measures'][0]['vehicleSet']['otherRestrictedTypeText'] = str_repeat('a', 101);
        $values['location_form']['measures'][0]['vehicleSet']['exemptedTypes'] = ['other'];
        $values['location_form']['measures'][0]['vehicleSet']['otherExemptedTypeText'] = str_repeat('a', 101);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#location_form_measures_0_vehicleSet_otherRestrictedTypeText_error')->text());
        $this->assertStringContainsString('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#location_form_measures_0_vehicleSet_otherExemptedTypeText_error')->text());
    }

    public function testInvalidVehicleSetBlankHeavyweightMaxWeight(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $this->assertEquals('3,5', $values['location_form']['measures'][0]['vehicleSet']['heavyweightMaxWeight']);
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'no';
        $values['location_form']['measures'][0]['vehicleSet']['restrictedTypes'] = ['heavyGoodsVehicle'];
        $values['location_form']['measures'][0]['vehicleSet']['heavyweightMaxWeight'] = ''; // Unset default

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_vehicleSet_heavyweightMaxWeight_error')->text());
    }

    public function testInvalidVehicleSetBlankDimensions(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'no';
        $values['location_form']['measures'][0]['vehicleSet']['restrictedTypes'] = ['dimensions'];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Veuillez spécifier le gabarit des véhicules concernés.', $crawler->filter('#location_form_measures_0_vehicleSet_restrictedTypes_error')->text());
    }

    public function testInvalidVehicleSetCharacteristicsNotNumber(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/location/add'); // Has no location yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'no';
        $values['location_form']['measures'][0]['vehicleSet']['restrictedTypes'] = ['heavyGoodsVehicle', 'dimensions'];
        $values['location_form']['measures'][0]['vehicleSet']['heavyweightMaxWeight'] = 'not a number';
        $values['location_form']['measures'][0]['vehicleSet']['maxWidth'] = 'true';
        $values['location_form']['measures'][0]['vehicleSet']['maxLength'] = [12];
        $values['location_form']['measures'][0]['vehicleSet']['maxHeight'] = '2.4m';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Veuillez saisir un nombre.', $crawler->filter('#location_form_measures_0_vehicleSet_heavyweightMaxWeight_error')->text());
        $this->assertStringContainsString('Veuillez saisir un nombre.', $crawler->filter('#location_form_measures_0_vehicleSet_maxWidth_error')->text());
        $this->assertStringContainsString('Veuillez saisir un nombre.', $crawler->filter('#location_form_measures_0_vehicleSet_maxLength_error')->text());
        $this->assertStringContainsString('Veuillez saisir un nombre.', $crawler->filter('#location_form_measures_0_vehicleSet_maxHeight_error')->text());
    }

    public function testInvalidVehicleSetCharacteristicsNotPositive(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'no';
        $values['location_form']['measures'][0]['vehicleSet']['restrictedTypes'] = ['heavyGoodsVehicle', 'dimensions'];
        $values['location_form']['measures'][0]['vehicleSet']['heavyweightMaxWeight'] = -1;
        $values['location_form']['measures'][0]['vehicleSet']['maxWidth'] = -0.1;
        $values['location_form']['measures'][0]['vehicleSet']['maxLength'] = -2.3;
        $values['location_form']['measures'][0]['vehicleSet']['maxHeight'] = -12;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur doit être supérieure ou égale à zéro.', $crawler->filter('#location_form_measures_0_vehicleSet_heavyweightMaxWeight_error')->text());
        $this->assertStringContainsString('Cette valeur doit être supérieure ou égale à zéro.', $crawler->filter('#location_form_measures_0_vehicleSet_maxWidth_error')->text());
        $this->assertStringContainsString('Cette valeur doit être supérieure ou égale à zéro.', $crawler->filter('#location_form_measures_0_vehicleSet_maxLength_error')->text());
        $this->assertStringContainsString('Cette valeur doit être supérieure ou égale à zéro.', $crawler->filter('#location_form_measures_0_vehicleSet_maxHeight_error')->text());
    }

    public function testInvalidBlankPeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Localisation', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['location_form']['cityCode'] = '44195';
        $values['location_form']['cityLabel'] = 'Savenay (44260)';
        $values['location_form']['roadName'] = 'Route du Grand Brossais';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'yes';
        $values['location_form']['measures'][0]['periods'][0]['recurrenceType'] = '';
        $values['location_form']['measures'][0]['periods'][0]['startTime']['hour'] = '';
        $values['location_form']['measures'][0]['periods'][0]['startTime']['minute'] = '';
        $values['location_form']['measures'][0]['periods'][0]['startDate'] = '';
        $values['location_form']['measures'][0]['periods'][0]['endDate'] = '';
        $values['location_form']['measures'][0]['periods'][0]['endTime']['hour'] = '';
        $values['location_form']['measures'][0]['periods'][0]['endTime']['minute'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_periods_0_startTime_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_periods_0_endTime_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_periods_0_startDate_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#location_form_measures_0_periods_0_endDate_error')->text());
    }

    public function testInvalidPeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Localisation', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Bad period
        $values = $form->getPhpValues();
        $values['location_form']['cityCode'] = '44195';
        $values['location_form']['cityLabel'] = 'Savenay (44260)';
        $values['location_form']['roadName'] = 'Route du Grand Brossais';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'yes';
        $values['location_form']['measures'][0]['periods'][0]['recurrenceType'] = 'everyDay';
        $values['location_form']['measures'][0]['periods'][0]['startDate'] = '2023-10-30';
        $values['location_form']['measures'][0]['periods'][0]['startTime']['hour'] = '10';
        $values['location_form']['measures'][0]['periods'][0]['startTime']['minute'] = '0';
        $values['location_form']['measures'][0]['periods'][0]['endDate'] = '2023-10-29';
        $values['location_form']['measures'][0]['periods'][0]['endTime']['hour'] = '8';
        $values['location_form']['measures'][0]['periods'][0]['endTime']['minute'] = '0';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('La date de fin doit être supérieure à la date de début.', $crawler->filter('#location_form_measures_0_periods_0_endDate_error')->text());

        // Bad values
        $values['location_form']['measures'][0]['periods'][0]['recurrenceType'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['startDate'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['startTime']['hour'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['startTime']['minute'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['endDate'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['endTime']['hour'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['endTime']['minute'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['dailyRange']['applicableDays'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['timeSlots'][0]['startTime']['hour'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['timeSlots'][0]['startTime']['minute'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['timeSlots'][0]['endTime']['hour'] = 'test';
        $values['location_form']['measures'][0]['periods'][0]['timeSlots'][0]['endTime']['minute'] = 'test';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Le choix sélectionné est invalide.', $crawler->filter('#location_form_measures_0_periods_0_dailyRange_applicableDays_error')->text());
        $this->assertStringContainsString('Veuillez saisir une heure valide.', $crawler->filter('#location_form_measures_0_periods_0_timeSlots_0_startTime_error')->text());
        $this->assertStringContainsString('Veuillez saisir une heure valide.', $crawler->filter('#location_form_measures_0_periods_0_timeSlots_0_endTime_error')->text());
        $this->assertSame('Le choix sélectionné est invalide.', $crawler->filter('#location_form_measures_0_periods_0_recurrenceType_error')->text());
        $this->assertStringContainsString('Veuillez saisir une heure valide.', $crawler->filter('#location_form_measures_0_periods_0_startTime_error')->text());
        $this->assertStringContainsString('Veuillez saisir une heure valide.', $crawler->filter('#location_form_measures_0_periods_0_endTime_error')->text());
        $this->assertSame('Veuillez entrer une date valide.', $crawler->filter('#location_form_measures_0_periods_0_endDate_error')->text());
        $this->assertSame('Veuillez entrer une date valide.', $crawler->filter('#location_form_measures_0_periods_0_startDate_error')->text());
    }

    public function testGeocodingFailure(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        // Get the raw values.
        $values = $form->getPhpValues();

        $values['location_form']['cityCode'] = '44195';
        $values['location_form']['cityLabel'] = 'Savenay (44260)';
        $values['location_form']['roadName'] = 'Route du GEOCODING_FAILURE';
        $values['location_form']['fromHouseNumber'] = '15';
        $values['location_form']['toHouseNumber'] = '37bis';
        $values['location_form']['measures'][0]['type'] = 'noEntry';
        $values['location_form']['measures'][0]['vehicleSet']['allVehicles'] = 'yes';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue.', $crawler->filter('#location_form_error')->text());
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/location/add');

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
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Annuler');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulation_location_add_link', ['regulationOrderRecordUuid' => RegulationOrderRecordFixture::UUID_TYPICAL]);
    }

    public function testFieldsTooLong(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $form['location_form[cityCode]'] = str_repeat('a', 6);
        $form['location_form[cityLabel]'] = str_repeat('a', 256);
        $form['location_form[roadName]'] = str_repeat('a', 256);
        $form['location_form[fromHouseNumber]'] = str_repeat('a', 9);
        $form['location_form[toHouseNumber]'] = str_repeat('a', 9);

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne doit avoir exactement 5 caractères.', $crawler->filter('#location_form_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#location_form_cityLabel_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#location_form_roadName_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.', $crawler->filter('#location_form_fromHouseNumber_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.', $crawler->filter('#location_form_toHouseNumber_error')->text());
    }

    public function testFieldsTooLongDepartmentalRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add?feature_road_type=true');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $form['location_form[roadType]'] = 'departmentalRoad';
        $form['location_form[administrator]'] = 'Ain';
        $form['location_form[roadNumber]'] = str_repeat('a', 51);

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 50 caractères.', $crawler->filter('#location_form_roadNumber_error')->text());
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/location/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}
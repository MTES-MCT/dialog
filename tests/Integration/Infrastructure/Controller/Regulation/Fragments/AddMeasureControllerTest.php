<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddMeasureControllerTest extends AbstractWebTestCase
{
    public function testInvalidBlank(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mesure', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['locations'] = []; // Remove the default empty location form

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide. Cette valeur doit être l\'un des choix proposés.', $crawler->filter('#measure_form_type_error')->text());
        $this->assertSame('Veuillez définir une ou plusieurs localisations.', $crawler->filter('#measure_form_locations_error')->text());
    }

    public function testInvalidBlankDepartmentalRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Mesure', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['locations'][0]['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['administrator'] = '';
        $values['measure_form']['locations'][0]['roadNumber'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles(), ['feature_road_type' => true]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_administrator_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_roadNumber_error')->text());
    }

    public function testInvalidCertainDaysWithoutApplicableDays(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add?feature_road_type=true');

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['periods'][0]['recurrenceType'] = 'certainDays';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles(), ['feature_road_type' => true]);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_periods_0_dailyRange_applicableDays_error')->text());
    }

    public function testAdd(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['heavyGoodsVehicle', 'dimensions', 'other'];
        $values['measure_form']['vehicleSet']['otherRestrictedTypeText'] = 'Matières dangereuses';
        $values['measure_form']['vehicleSet']['exemptedTypes'] = ['commercial', 'other'];
        $values['measure_form']['vehicleSet']['otherExemptedTypeText'] = 'Déchets industriels';
        $values['measure_form']['vehicleSet']['heavyweightMaxWeight'] = 3.5;
        $values['measure_form']['vehicleSet']['maxWidth'] = 0.0; // Zero OK
        $values['measure_form']['vehicleSet']['maxLength'] = -0; // Zero OK
        $values['measure_form']['vehicleSet']['maxHeight'] = '2.50';
        $values['measure_form']['periods'][0]['isPermanent'] = '1';
        $values['measure_form']['periods'][0]['recurrenceType'] = 'certainDays';
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '8';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['dailyRange']['applicableDays'] = ['monday'];
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['hour'] = '8';
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['hour'] = '20';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['minute'] = '0';

        $values['measure_form']['locations'][0]['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['roadName'] = 'Route du Grand Brossais';
        unset($values['measure_form']['locations'][0]['isEntireStreet']);
        $values['measure_form']['locations'][0]['fromHouseNumber'] = '15';
        $values['measure_form']['locations'][0]['toHouseNumber'] = '37bis';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(200);

        $streams = $crawler->filter('turbo-stream')->extract(['action', 'target']);

        $this->assertEquals([
            ['replace', 'measure_' . MeasureFixture::UUID_PERMANENT_ONLY_ONE . '_delete_button'],
            ['append', 'measure_list'],
            ['replace', 'block_measure'],
            ['replace', 'block_export'],
            ['update', 'block_publication'],
        ], $streams);

        $addMeasureBtn = $crawler->filter('turbo-stream[target=block_measure]')->selectButton('Ajouter une mesure');
        $this->assertSame('http://localhost/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/measure/add', $addMeasureBtn->form()->getUri());
    }

    public function testAddDepartmentalRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/measure/add?feature_road_type=true');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['locations'][0]['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['administrator'] = 'Ain';
        $values['measure_form']['locations'][0]['roadNumber'] = 'D1075';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles(), ['feature_road_type' => true]);

        $this->assertResponseStatusCodeSame(200);

        $measures = $crawler->filter('[data-testid="measure"]');

        $this->assertSame('Circulation interdite', $measures->eq(0)->filter('h3')->text());
        $this->assertSame('D1075 (Ain)', $measures->eq(0)->filter('.app-card__content li')->eq(3)->text());
    }

    public function testInvalidVehicleSetBlankRestrictedTypes(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'no';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $this->assertSame($values['measure_form']['vehicleSet']['heavyweightMaxWeight'], '3,5');
        $this->assertStringContainsString('Veuillez spécifier un ou plusieurs véhicules concernés.', $crawler->filter('#measure_form_vehicleSet_restrictedTypes_error')->text());
    }

    public function testInvalidVehicleSetBlankOtherRestrictedTypeText(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'no';
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['other'];
        $values['measure_form']['vehicleSet']['otherRestrictedTypeText'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_vehicleSet_otherRestrictedTypeText_error')->text());
    }

    public function testInvalidVehicleSetBlankOtherExemptedTypeText(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['vehicleSet']['exemptedTypes'] = ['other'];
        $values['measure_form']['vehicleSet']['otherExemptedTypeText'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_vehicleSet_otherExemptedTypeText_error')->text());
    }

    public function testInvalidBlankCritairTypes(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['roadName'] = 'Route du Grand Brossais';
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['critair'];
        $values['measure_form']['vehicleSet']['critairTypes'] = [];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_vehicleSet_critairTypes_error')->text());
    }

    public function testInvalidCritairTypes(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['roadName'] = 'Route du Grand Brossais';
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['critair'];
        $values['measure_form']['vehicleSet']['critairTypes'] = ['invalidCritair'];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Le choix sélectionné est invalide.', $crawler->filter('#measure_form_vehicleSet_critairTypes_error')->text());
    }

    public function testInvalidVehicleSetOtherTextsTooLong(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'no';
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['other'];
        $values['measure_form']['vehicleSet']['otherRestrictedTypeText'] = str_repeat('a', 101);
        $values['measure_form']['vehicleSet']['exemptedTypes'] = ['other'];
        $values['measure_form']['vehicleSet']['otherExemptedTypeText'] = str_repeat('a', 301);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#measure_form_vehicleSet_otherRestrictedTypeText_error')->text());
        $this->assertStringContainsString('Cette chaîne est trop longue. Elle doit avoir au maximum 300 caractères.', $crawler->filter('#measure_form_vehicleSet_otherExemptedTypeText_error')->text());
    }

    public function testInvalidVehicleSetBlankDimensions(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'no';
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['dimensions'];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Veuillez spécifier le gabarit des véhicules concernés.', $crawler->filter('#measure_form_vehicleSet_restrictedTypes_error')->text());
    }

    public function testInvalidVehicleSetCharacteristicsNotNumber(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/4ce75a1f-82f3-40ee-8f95-48d0f04446aa/measure/add'); // Has no measure yet
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'no';
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['heavyGoodsVehicle', 'dimensions'];
        $values['measure_form']['vehicleSet']['heavyweightMaxWeight'] = 'not a number';
        $values['measure_form']['vehicleSet']['maxWidth'] = 'true';
        $values['measure_form']['vehicleSet']['maxLength'] = [12];
        $values['measure_form']['vehicleSet']['maxHeight'] = '2.4m';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Veuillez saisir un nombre.', $crawler->filter('#measure_form_vehicleSet_heavyweightMaxWeight_error')->text());
        $this->assertStringContainsString('Veuillez saisir un nombre.', $crawler->filter('#measure_form_vehicleSet_maxWidth_error')->text());
        $this->assertStringContainsString('Veuillez saisir un nombre.', $crawler->filter('#measure_form_vehicleSet_maxLength_error')->text());
        $this->assertStringContainsString('Veuillez saisir un nombre.', $crawler->filter('#measure_form_vehicleSet_maxHeight_error')->text());
    }

    public function testInvalidVehicleSetCharacteristicsNotPositive(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'no';
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['heavyGoodsVehicle', 'dimensions'];
        $values['measure_form']['vehicleSet']['heavyweightMaxWeight'] = -1;
        $values['measure_form']['vehicleSet']['maxWidth'] = -0.1;
        $values['measure_form']['vehicleSet']['maxLength'] = -2.3;
        $values['measure_form']['vehicleSet']['maxHeight'] = -12;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur doit être supérieure ou égale à zéro.', $crawler->filter('#measure_form_vehicleSet_heavyweightMaxWeight_error')->text());
        $this->assertStringContainsString('Cette valeur doit être supérieure ou égale à zéro.', $crawler->filter('#measure_form_vehicleSet_maxWidth_error')->text());
        $this->assertStringContainsString('Cette valeur doit être supérieure ou égale à zéro.', $crawler->filter('#measure_form_vehicleSet_maxLength_error')->text());
        $this->assertStringContainsString('Cette valeur doit être supérieure ou égale à zéro.', $crawler->filter('#measure_form_vehicleSet_maxHeight_error')->text());
    }

    public function testPermanentInvalidBlankPeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['roadName'] = 'Route du Grand Brossais';
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['periods'][0]['isPermanent'] = '1';
        $values['measure_form']['periods'][0]['recurrenceType'] = '';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '';
        $values['measure_form']['periods'][0]['startDate'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_periods_0_startTime_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_periods_0_startDate_error')->text());
    }

    public function testTemporaryInvalidBlankPeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['roadName'] = 'Route du Grand Brossais';
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['periods'][0]['isPermanent'] = '0';
        $values['measure_form']['periods'][0]['recurrenceType'] = '';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '';
        $values['measure_form']['periods'][0]['startDate'] = '';
        $values['measure_form']['periods'][0]['endDate'] = '';
        $values['measure_form']['periods'][0]['endTime']['hour'] = '';
        $values['measure_form']['periods'][0]['endTime']['minute'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_periods_0_startTime_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_periods_0_endTime_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_periods_0_startDate_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_periods_0_endDate_error')->text());
    }

    public function testInvalidPeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Bad period
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['roadName'] = 'Route du Grand Brossais';
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['periods'][0]['recurrenceType'] = 'everyDay';
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '10';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['endDate'] = '2023-10-29';
        $values['measure_form']['periods'][0]['endTime']['hour'] = '8';
        $values['measure_form']['periods'][0]['endTime']['minute'] = '0';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('La date de fin doit être supérieure à la date de début.', $crawler->filter('#measure_form_periods_0_endDate_error')->text());

        // Bad values
        $values['measure_form']['periods'][0]['recurrenceType'] = 'test';
        $values['measure_form']['periods'][0]['startDate'] = 'test';
        $values['measure_form']['periods'][0]['startTime']['hour'] = 'test';
        $values['measure_form']['periods'][0]['startTime']['minute'] = 'test';
        $values['measure_form']['periods'][0]['endDate'] = 'test';
        $values['measure_form']['periods'][0]['endTime']['hour'] = 'test';
        $values['measure_form']['periods'][0]['endTime']['minute'] = 'test';
        $values['measure_form']['periods'][0]['dailyRange']['applicableDays'] = 'test';
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['hour'] = 'test';
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['minute'] = 'test';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['hour'] = 'test';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['minute'] = 'test';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Le choix sélectionné est invalide.', $crawler->filter('#measure_form_periods_0_dailyRange_applicableDays_error')->text());
        $this->assertStringContainsString('Veuillez saisir une heure valide.', $crawler->filter('#measure_form_periods_0_timeSlots_0_startTime_error')->text());
        $this->assertStringContainsString('Veuillez saisir une heure valide.', $crawler->filter('#measure_form_periods_0_timeSlots_0_endTime_error')->text());
        $this->assertSame('Le choix sélectionné est invalide.', $crawler->filter('#measure_form_periods_0_recurrenceType_error')->text());
        $this->assertStringContainsString('Veuillez saisir une heure valide.', $crawler->filter('#measure_form_periods_0_startTime_error')->text());
        $this->assertStringContainsString('Veuillez saisir une heure valide.', $crawler->filter('#measure_form_periods_0_endTime_error')->text());
        $this->assertSame('Veuillez entrer une date valide.', $crawler->filter('#measure_form_periods_0_endDate_error')->text());
        $this->assertSame('Veuillez entrer une date valide.', $crawler->filter('#measure_form_periods_0_startDate_error')->text());
    }

    public function testGeocodingFailure(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        // Get the raw values.
        $values = $form->getPhpValues();

        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['locations'][0]['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['roadName'] = 'Route du GEOCODING_FAILURE';
        unset($values['location_form']['isEntireStreet']);
        $values['measure_form']['locations'][0]['fromHouseNumber'] = '15';
        $values['measure_form']['locations'][0]['toHouseNumber'] = '37bis';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue.', $crawler->filter('#measure_form_locations_0_roadName_error')->text());
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/measure/add');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/aaaaaaaa/measure/add');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testCancel(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Annuler');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulation_measure_add_link', ['regulationOrderRecordUuid' => RegulationOrderRecordFixture::UUID_TYPICAL]);
    }

    public function testFieldsTooLong(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['cityCode'] = str_repeat('a', 6);
        $values['measure_form']['locations'][0]['cityLabel'] = str_repeat('a', 256);
        $values['measure_form']['locations'][0]['roadName'] = str_repeat('a', 256);
        unset($values['location_form']['isEntireStreet']);
        $values['measure_form']['locations'][0]['fromHouseNumber'] = str_repeat('a', 9);
        $values['measure_form']['locations'][0]['toHouseNumber'] = str_repeat('a', 9);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne doit avoir exactement 5 caractères.', $crawler->filter('#measure_form_locations_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#measure_form_locations_0_cityLabel_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#measure_form_locations_0_roadName_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.', $crawler->filter('#measure_form_locations_0_fromHouseNumber_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.', $crawler->filter('#measure_form_locations_0_toHouseNumber_error')->text());
    }

    public function testFieldsTooLongDepartmentalRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add?feature_road_type=true');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['administrator'] = 'Ain';
        $values['measure_form']['locations'][0]['roadNumber'] = str_repeat('a', 51);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 50 caractères.', $crawler->filter('#measure_form_locations_0_roadNumber_error')->text());
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

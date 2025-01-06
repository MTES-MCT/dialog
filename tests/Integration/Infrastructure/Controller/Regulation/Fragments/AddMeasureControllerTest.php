<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\RoadTypeEnum;
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

    public function testInvalidLaneBlankCityCode(): void
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
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = ''; // Blank
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Savenay';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Route du Grand Manual Input';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Veuillez choisir une ville ou un code postal parmi la liste.', $crawler->filter('#measure_form_locations_0_namedStreet_cityLabel_error')->text());
    }

    public function testInvalidDepartmentalRoad(): void
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
        $values['measure_form']['locations'][0]['departmentalRoad']['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['departmentalRoad']['administrator'] = '';
        $values['measure_form']['locations'][0]['departmentalRoad']['roadNumber'] = '';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromPointNumber'] = '';
        $values['measure_form']['locations'][0]['departmentalRoad']['toPointNumber'] = '';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromSide'] = 'A';
        $values['measure_form']['locations'][0]['departmentalRoad']['toSide'] = 'A';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromAbscissa'] = 'A';
        $values['measure_form']['locations'][0]['departmentalRoad']['toAbscissa'] = 'A';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_departmentalRoad_roadNumber_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_departmentalRoad_administrator_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_departmentalRoad_fromPointNumber_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_departmentalRoad_toPointNumber_error')->text());
        $this->assertSame('Le choix sélectionné est invalide.', $crawler->filter('#measure_form_locations_0_departmentalRoad_fromSide_error')->text());
        $this->assertSame('Le choix sélectionné est invalide.', $crawler->filter('#measure_form_locations_0_departmentalRoad_toSide_error')->text());
        $this->assertSame('Veuillez saisir un entier.', $crawler->filter('#measure_form_locations_0_departmentalRoad_fromAbscissa_error')->text());
        $this->assertSame('Veuillez saisir un entier.', $crawler->filter('#measure_form_locations_0_departmentalRoad_toAbscissa_error')->text());
    }

    public function testInvalidCertainDaysWithoutApplicableDays(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['periods'][0]['recurrenceType'] = 'certainDays';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
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
        $values['measure_form']['periods'][0]['dailyRange']['applicableDays'] = ['monday'];
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['hour'] = '8';
        $values['measure_form']['periods'][0]['timeSlots'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['hour'] = '20';
        $values['measure_form']['periods'][0]['timeSlots'][0]['endTime']['minute'] = '0';

        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Route du Grand Brossais';
        unset($values['measure_form']['locations'][0]['namedStreet']['isEntireStreet']);
        $values['measure_form']['locations'][0]['namedStreet']['fromHouseNumber'] = '15';
        $values['measure_form']['locations'][0]['namedStreet']['toHouseNumber'] = '37bis';
        $values['measure_form']['locations'][0]['namedStreet']['direction'] = DirectionEnum::BOTH->value;

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

    public function testGeocodingFailureFullRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['isEntireStreet'] = '1';
        $values['measure_form']['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '59368';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'La Madeleine (59110)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Rue de NOT_HANDLED_BY_MOCK';
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue. Vérifier le nom de la voie, et les numéros de début et fin.', $crawler->filter('#measure_form_locations_0_namedStreet_roadName_error')->text());
    }

    public function testAddLaneWithBlankHouseNumbers(): void
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
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Route du Grand Brossais';
        unset($values['measure_form']['locations'][0]['namedStreet']['isEntireStreet']);
        $values['measure_form']['locations'][0]['namedStreet']['fromPointType'] = 'houseNumber';
        $values['measure_form']['locations'][0]['namedStreet']['fromHouseNumber'] = '';
        $values['measure_form']['locations'][0]['namedStreet']['toPointType'] = 'houseNumber';
        $values['measure_form']['locations'][0]['namedStreet']['toHouseNumber'] = '';
        $values['measure_form']['locations'][0]['namedStreet']['direction'] = DirectionEnum::BOTH->value;
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_namedStreet_fromHouseNumber_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_namedStreet_toHouseNumber_error')->text());
    }

    public function testAddLaneWithBlankFromRoadName(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Route du Grand Brossais';
        unset($values['measure_form']['locations'][0]['namedStreet']['isEntireStreet']);
        $values['measure_form']['locations'][0]['namedStreet']['fromPointType'] = 'intersection';
        $values['measure_form']['locations'][0]['namedStreet']['fromRoadName'] = '';
        $values['measure_form']['locations'][0]['namedStreet']['toPointType'] = 'houseNumber';
        $values['measure_form']['locations'][0]['namedStreet']['toRoadName'] = '15';
        $values['measure_form']['locations'][0]['namedStreet']['direction'] = DirectionEnum::BOTH->value;
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '0';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['endDate'] = '2023-10-31';
        $values['measure_form']['periods'][0]['endTime']['hour'] = '23';
        $values['measure_form']['periods'][0]['endTime']['minute'] = '59';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_namedStreet_fromRoadName_error')->text());
    }

    public function testAddLaneWithBlankToRoadName(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Route du Grand Brossais';
        unset($values['measure_form']['locations'][0]['namedStreet']['isEntireStreet']);
        $values['measure_form']['locations'][0]['namedStreet']['fromPointType'] = 'houseNumber';
        $values['measure_form']['locations'][0]['namedStreet']['fromRoadName'] = '15';
        $values['measure_form']['locations'][0]['namedStreet']['toPointType'] = 'intersection';
        $values['measure_form']['locations'][0]['namedStreet']['toRoadName'] = '';
        $values['measure_form']['locations'][0]['namedStreet']['direction'] = DirectionEnum::BOTH->value;
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '0';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['endDate'] = '2023-10-31';
        $values['measure_form']['periods'][0]['endTime']['hour'] = '23';
        $values['measure_form']['periods'][0]['endTime']['minute'] = '59';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_namedStreet_toRoadName_error')->text());
    }

    public function testAddLaneWithUnknownHouseNumbers(): void
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
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Route du Grand Brossais';
        unset($values['measure_form']['locations'][0]['namedStreet']['isEntireStreet']);
        $values['measure_form']['locations'][0]['namedStreet']['fromHouseNumber'] = '15';
        $values['measure_form']['locations'][0]['namedStreet']['toHouseNumber'] = '999'; // Mock will return no result
        $values['measure_form']['locations'][0]['namedStreet']['direction'] = DirectionEnum::BOTH->value;
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('La géolocalisation de la voie entre ces points a échoué', $crawler->filter('#measure_form_locations_0_namedStreet_fromPointType_error')->text());
    }

    public function testAddLaneWithHouseNumbersOnMultipleSections(): void
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
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '59606';
        $values['measure_form']['locations'][0]['namedStreet']['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Valenciennes (59300)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Rue du Faubourg de Paris';
        unset($values['measure_form']['locations'][0]['namedStreet']['isEntireStreet']);
        $values['measure_form']['locations'][0]['namedStreet']['fromHouseNumber'] = '80';
        $values['measure_form']['locations'][0]['namedStreet']['toHouseNumber'] = '44'; // Not on same section than 80
        $values['measure_form']['locations'][0]['namedStreet']['direction'] = DirectionEnum::BOTH->value;
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('La géolocalisation de la voie entre ces points a échoué', $crawler->filter('#measure_form_locations_0_namedStreet_fromPointType_error')->text());
    }

    private function provideTestAddNumberedRoad(): array
    {
        return [
            'departmentalRoad' => [
                'locationForm' => [
                    'roadType' => RoadTypeEnum::DEPARTMENTAL_ROAD->value,
                    RoadTypeEnum::DEPARTMENTAL_ROAD->value => [
                        'administrator' => 'Ardèche',
                        'roadNumber' => 'D906',
                        'fromPointNumber' => '34',
                        'fromSide' => 'U',
                        'fromAbscissa' => '100',
                        'toPointNumber' => '35',
                        'toSide' => 'U',
                        'toAbscissa' => '650',
                    ],
                ],
                'expectedText' => 'D906 (Ardèche) du PR 34+100 (côté U) au PR 35+650 (côté U)',
            ],
            'nationalRoad' => [
                'locationForm' => [
                    'roadType' => RoadTypeEnum::NATIONAL_ROAD->value,
                    RoadTypeEnum::NATIONAL_ROAD->value => [
                        'administrator' => 'DIR Ouest',
                        'roadNumber' => 'N176',
                        'fromPointNumber' => '1',
                        'fromSide' => 'D',
                        'fromAbscissa' => '0',
                        'toPointNumber' => '2',
                        'toSide' => 'D',
                        'toAbscissa' => '50',
                    ],
                ],
                'expectedText' => 'N176 (DIR Ouest) du PR 1+0 (côté D) au PR 2+50 (côté D)',
            ],
        ];
    }

    /**
     * @dataProvider provideTestAddNumberedRoad
     */
    public function testAddNumberedRoad(array $locationForm, string $expectedText): void
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
        $values['measure_form']['locations'][0] = $locationForm;
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(200);

        $measures = $crawler->filter('[data-testid="measure"]');

        $this->assertSame('Circulation interdite', $measures->eq(0)->filter('h3')->text());
        $this->assertSame($expectedText, $measures->eq(0)->filter('.app-card__content li')->eq(3)->text());
    }

    public function testAddDepartmentalRoadWithUnknownPointNumbers(): void
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
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('La géolocalisation de la route entre ces points de repère a échoué', $crawler->filter('#measure_form_locations_0_departmentalRoad_roadNumber_error')->text());
    }

    public function testAddDepartmentalRoadWithStartAbscissaOutOfRange(): void
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
        $values['measure_form']['locations'][0]['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['departmentalRoad']['administrator'] = 'Ardèche';
        $values['measure_form']['locations'][0]['departmentalRoad']['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['departmentalRoad']['roadNumber'] = 'D110';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromPointNumber'] = '1';
        $values['measure_form']['locations'][0]['departmentalRoad']['toPointNumber'] = '5';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromSide'] = 'U';
        $values['measure_form']['locations'][0]['departmentalRoad']['toSide'] = 'U';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromAbscissa'] = 100000000;
        $values['measure_form']['locations'][0]['departmentalRoad']['toAbscissa'] = 650;
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette abscisse n\'est pas située sur la route. Veuillez vérifier votre saisie.', $crawler->filter('#measure_form_locations_0_departmentalRoad_fromAbscissa_error')->text());
    }

    public function testAddDepartmentalRoadWithEndAbscissaOutOfRange(): void
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
        $values['measure_form']['locations'][0]['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['departmentalRoad']['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['departmentalRoad']['administrator'] = 'Ardèche';
        $values['measure_form']['locations'][0]['departmentalRoad']['roadNumber'] = 'D110';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromPointNumber'] = '1';
        $values['measure_form']['locations'][0]['departmentalRoad']['toPointNumber'] = '5';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromSide'] = 'U';
        $values['measure_form']['locations'][0]['departmentalRoad']['toSide'] = 'U';
        $values['measure_form']['locations'][0]['departmentalRoad']['fromAbscissa'] = 100;
        $values['measure_form']['locations'][0]['departmentalRoad']['toAbscissa'] = 100000000;
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette abscisse n\'est pas située sur la route. Veuillez vérifier votre saisie.', $crawler->filter('#measure_form_locations_0_departmentalRoad_toAbscissa_error')->text());
    }

    public function testAddRawGeoJSONAsUserHidden(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $roadTypeOptions = $crawler->filter('#measure_form_locations_0_roadType')->filter('option');
        $this->assertSame('Données brutes GeoJSON', $roadTypeOptions->eq(4)->innerText());
        $this->assertSame('', $roadTypeOptions->eq(4)->attr('hidden'));
        $this->assertSame('disabled', $roadTypeOptions->eq(4)->attr('disabled'));
    }

    public function testAddRawGeoJSONAsAdmin(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['locations'][0]['roadType'] = 'rawGeoJSON';
        $values['measure_form']['locations'][0]['rawGeoJSON']['label'] = 'Village olympique';
        $values['measure_form']['locations'][0]['rawGeoJSON']['geometry'] = '{"type": "Point", "coordinates": [42, 4]}';
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-30';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '0';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['endDate'] = '2023-10-31';
        $values['measure_form']['periods'][0]['endTime']['hour'] = '23';
        $values['measure_form']['periods'][0]['endTime']['minute'] = '59';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(200);

        $measures = $crawler->filter('[data-testid="measure"]');

        $this->assertSame('Circulation interdite', $measures->eq(0)->filter('h3')->text());
        $this->assertSame('Village olympique (données brutes geojson)', $measures->eq(0)->filter('.app-card__content li')->eq(3)->text());
    }

    public function testAddRawGeoJSONAsAdminInvalidBlank(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['locations'][0]['roadType'] = 'rawGeoJSON';
        $values['measure_form']['locations'][0]['rawGeoJSON']['label'] = '';
        $values['measure_form']['locations'][0]['rawGeoJSON']['geometry'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_rawGeoJSON_label_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_locations_0_rawGeoJSON_geometry_error')->text());
    }

    public function testAddRawGeoJSONAsAdminInvalidGeometryJSON(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_RAWGEOJSON . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['allVehicles'] = 'yes';
        $values['measure_form']['locations'][0]['roadType'] = 'rawGeoJSON';
        $values['measure_form']['locations'][0]['rawGeoJSON']['label'] = 'Test';
        $values['measure_form']['locations'][0]['rawGeoJSON']['geometry'] = '{notvalidjson'; // Trigger invalid JSON error

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertStringStartsWith('Cette valeur doit être une géométrie GeoJSON valide', $crawler->filter('#measure_form_locations_0_rawGeoJSON_geometry_error')->text());
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
        $this->assertStringContainsString('Veuillez spécifier un ou plusieurs véhicules concernés.', $crawler->filter('#measure_form_vehicleSet_restrictedTypes_error')->text());
    }

    public function testInvalidVehicleSetBlankHeavyweightMaxWeight(): void
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
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['heavyGoodsVehicle'];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_vehicleSet_heavyweightMaxWeight_error')->text());
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
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Route du Grand Brossais';
        $values['measure_form']['type'] = 'noEntry';
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['critair'];
        $values['measure_form']['vehicleSet']['critairTypes'] = [];

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
        $this->assertStringContainsString('Cette valeur ne doit pas être vide.', $crawler->filter('#measure_form_vehicleSet_critairTypes_error')->text());
    }

    public function testHeavyweightMaxWeightChoices(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/measure/add');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $choices = $crawler
            ->filter('select[name="measure_form[vehicleSet][heavyweightMaxWeight]"] > option')
            ->each(fn ($node) => [$node->attr('value'), $node->text()]);

        $this->assertEquals([
            ['', 'Sélectionner le poids'],
            ['3.5', 3.5],
            ['7.5', 7.5],
            ['19', 19],
            ['26', 26],
            ['32', 32],
            ['44', 44],
        ], $choices);
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
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Route du Grand Brossais';
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
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['dimensions'];
        $values['measure_form']['vehicleSet']['maxWidth'] = 'true';
        $values['measure_form']['vehicleSet']['maxLength'] = [12];
        $values['measure_form']['vehicleSet']['maxHeight'] = '2.4m';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
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
        $values['measure_form']['vehicleSet']['restrictedTypes'] = ['dimensions'];
        $values['measure_form']['vehicleSet']['maxWidth'] = -0.1;
        $values['measure_form']['vehicleSet']['maxLength'] = -2.3;
        $values['measure_form']['vehicleSet']['maxHeight'] = -12;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values);
        $this->assertResponseStatusCodeSame(422);
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
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = '44195';
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = 'Savenay (44260)';
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = 'Route du Grand Brossais';
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

        // Bad period time
        $values['measure_form']['periods'][0]['startDate'] = '2023-10-29';
        $values['measure_form']['periods'][0]['startTime']['hour'] = '10';
        $values['measure_form']['periods'][0]['startTime']['minute'] = '0';
        $values['measure_form']['periods'][0]['endDate'] = '2023-10-29';
        $values['measure_form']['periods'][0]['endTime']['hour'] = '8';
        $values['measure_form']['periods'][0]['endTime']['minute'] = '0';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('L\'heure de fin doit être supérieure à l\'heure de début.', $crawler->filter('#measure_form_periods_0_endTime_error')->text());

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
        $values['measure_form']['locations'][0]['roadType'] = 'lane';
        $values['measure_form']['locations'][0]['namedStreet']['cityCode'] = str_repeat('a', 6);
        $values['measure_form']['locations'][0]['namedStreet']['cityLabel'] = str_repeat('a', 256);
        $values['measure_form']['locations'][0]['namedStreet']['roadName'] = str_repeat('a', 256);
        unset($values['location_form']['namedStreet']['isEntireStreet']);
        $values['measure_form']['locations'][0]['namedStreet']['fromHouseNumber'] = str_repeat('a', 9);
        $values['measure_form']['locations'][0]['namedStreet']['fromRoadName'] = str_repeat('a', 256);
        $values['measure_form']['locations'][0]['namedStreet']['toHouseNumber'] = str_repeat('a', 9);
        $values['measure_form']['locations'][0]['namedStreet']['toRoadName'] = str_repeat('a', 256);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne doit avoir exactement 5 caractères. Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#measure_form_locations_0_namedStreet_cityLabel_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#measure_form_locations_0_namedStreet_roadName_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.', $crawler->filter('#measure_form_locations_0_namedStreet_fromHouseNumber_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#measure_form_locations_0_namedStreet_fromRoadName_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 8 caractères.', $crawler->filter('#measure_form_locations_0_namedStreet_toHouseNumber_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#measure_form_locations_0_namedStreet_toRoadName_error')->text());
    }

    public function testFieldsTooLongDepartmentalRoad(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['roadType'] = 'departmentalRoad';
        $values['measure_form']['locations'][0]['departmentalRoad']['administrator'] = 'Ain';
        $values['measure_form']['locations'][0]['departmentalRoad']['roadNumber'] = str_repeat('a', 51);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 50 caractères.', $crawler->filter('#measure_form_locations_0_departmentalRoad_roadNumber_error')->text());
    }

    public function testFieldsTooLongRawGeoJSON(): void
    {
        $client = $this->login(UserFixture::MAIN_ORG_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['roadType'] = 'rawGeoJSON';
        $values['measure_form']['locations'][0]['rawGeoJSON']['geometry'] = 'geom';
        $values['measure_form']['locations'][0]['rawGeoJSON']['label'] = str_repeat('a', 5001);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 5000 caractères.', $crawler->filter('#measure_form_locations_0_rawGeoJSON_label_error')->text());
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

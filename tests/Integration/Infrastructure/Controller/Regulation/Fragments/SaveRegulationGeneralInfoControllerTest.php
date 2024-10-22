<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class SaveRegulationGeneralInfoControllerTest extends AbstractWebTestCase
{
    public function testEditWithAnAlreadyExistingIdentifier(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_PERMANENT);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $identifier = RegulationOrderFixture::TYPICAL_IDENTIFIER;

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['general_info_form']['identifier'] = $identifier;
        $values['general_info_form']['organization'] = OrganizationFixture::MAIN_ORG_ID;
        $values['general_info_form']['description'] = 'Interdiction de circuler dans Paris';
        $values['general_info_form']['startDate'] = '2023-02-12';
        $values['general_info_form']['endDate'] = '2024-02-11';
        $values['general_info_form']['category'] = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;
        $values['general_info_form']['otherCategoryText'] = 'Travaux';
        $values['general_info_form']['additionalVisas'][0] = 'Vu 1';
        $values['general_info_form']['additionalVisas'][1] = 'Vu 2';
        $values['general_info_form']['additionalReasons'][0] = 'Motif 1';
        $values['general_info_form']['additionalReasons'][1] = 'Motif 2';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame(\sprintf('Un arrêté avec l\'identifiant "%s" existe déjà. Veuillez saisir un autre identifiant.', $identifier), $crawler->filter('#general_info_form_identifier_error')->text());
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/general_info/form/aaaaaaaa');

        $this->assertResponseStatusCodeSame(400);
    }

    public function testFieldsTooLong(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['general_info_form[description]'] = str_repeat('a', 256);
        $form['general_info_form[identifier]'] = str_repeat('a', 61);

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 60 caractères.', $crawler->filter('#general_info_form_identifier_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#general_info_form_description_error')->text());
    }

    public function testEmptyReasonsAndVisas(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['general_info_form']['additionalVisas'][0] = '';
        $values['general_info_form']['additionalReasons'][0] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#general_info_form_additionalVisas_0_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#general_info_form_additionalReasons_0_error')->text());
    }

    public function testCannotAccessBecauseDifferentOrganization(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_TYPICAL);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

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
        $this->assertSame('Description 3', $crawler->filter('h3')->text());

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['general_info_form[identifier]'] = RegulationOrderFixture::TYPICAL_IDENTIFIER;
        $form['general_info_form[organization]'] = OrganizationFixture::MAIN_ORG_ID;
        $form['general_info_form[category]'] = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;
        $form['general_info_form[description]'] = 'Travaux';
        $form['general_info_form[startDate]'] = '2023-02-12';
        $form['general_info_form[endDate]'] = '2024-02-11';
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Un arrêté avec cet identifiant existe déjà. Veuillez saisir un autre identifiant.', $crawler->filter('#general_info_form_identifier_error')->text());
    }

    public function testEditDescriptionTruncated(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_LONG_DESCRIPTION);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Description 5 that is very long and...', $crawler->filter('h3')->text());
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST);

        $this->assertResponseStatusCodeSame(404);
    }

    public function testEditRegulationOrderWithNoStartDateYet(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $crawler = $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_OTHER_ORG_NO_START_DATE);

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['general_info_form[identifier]'] = 'FIOIUS';
        $form['general_info_form[organization]'] = OrganizationFixture::OTHER_ORG_ID;
        $form['general_info_form[description]'] = 'Interdiction de circuler dans Paris';
        $form['general_info_form[startDate]'] = '2023-02-14';
        $form['general_info_form[category]'] = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_regulations_general_info');
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

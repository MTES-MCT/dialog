<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Domain\Regulation\Enum\RegulationSubjectEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class SaveRegulationGeneralInfoControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_PERMANENT);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['general_info_form']['title'] = 'Nouveau titre';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(200);

        // Sans changement de catégorie, les blocs de mesures ne sont pas re-rendus
        $streams = $crawler->filter('turbo-stream')->extract(['action', 'target']);
        $this->assertEquals([
            ['replace', 'block_general_info'],
            ['update', 'regulation-detail'],
            ['update', 'block_publication'],
        ], $streams);
        $this->assertSame('Arrêté permanent FO3/2023', $crawler->filter('turbo-stream[target="regulation-detail"]')->text());

        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT);
        $this->assertSame('Modifié le 09/06/2023 à 01h00 par Mathieu MARCHOIS', $crawler->filter('[data-testid="history"]')->text());
    }

    public function testEditCategoryChangeUpdatesTitleAndMeasures(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_PERMANENT);
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        $values['general_info_form']['category'] = RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value;
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(200);

        $streams = $crawler->filter('turbo-stream')->extract(['action', 'target']);
        $this->assertEquals([
            ['replace', 'block_general_info'],
            ['update', 'regulation-detail'],
            ['replace', 'block_measure_' . MeasureFixture::UUID_PERMANENT_ONLY_ONE],
            ['replace', 'block_measure'],
            ['update', 'block_publication'],
        ], $streams);
        $this->assertSame('Arrêté temporaire FO3/2023', $crawler->filter('turbo-stream[target="regulation-detail"]')->text());

        $addMeasureBtn = $crawler->filter('turbo-stream[target="block_measure"]')->selectButton('Ajouter une mesure');
        $this->assertSame('http://localhost/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/measure/add', $addMeasureBtn->form()->getUri());
    }

    public function testEditCategoryChangeWithoutMeasureRendersAddMeasureForm(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/_fragment/regulations/general_info/form/' . RegulationOrderRecordFixture::UUID_NO_LOCATIONS);
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        $values['general_info_form']['category'] = RegulationOrderCategoryEnum::PERMANENT_REGULATION->value;
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(200);

        $streams = $crawler->filter('turbo-stream')->extract(['action', 'target']);
        $this->assertEquals([
            ['replace', 'block_general_info'],
            ['update', 'regulation-detail'],
            ['replace', 'block_measure'],
            ['update', 'block_publication'],
        ], $streams);
        $this->assertSame('Arrêté permanent F2023/no-locations', $crawler->filter('turbo-stream[target="regulation-detail"]')->text());

        // Sans mesure existante, le formulaire d'ajout est re-rendu avec la nouvelle catégorie
        $this->assertCount(1, $crawler->filter('turbo-stream[target="block_measure"] turbo-frame#block_measure form'));
    }

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
        $values['general_info_form']['organization'] = OrganizationFixture::SEINE_SAINT_DENIS_ID;
        $values['general_info_form']['title'] = 'Interdiction de circuler dans Paris';
        $values['general_info_form']['category'] = RegulationOrderCategoryEnum::TEMPORARY_REGULATION->value;
        $values['general_info_form']['subject'] = RegulationSubjectEnum::ROAD_MAINTENANCE->value;
        $values['general_info_form']['otherCategoryText'] = 'Travaux';
        $values['general_info_form']['regulationOrderTemplateUuid'] = 'ba023736-35f6-49f4-a118-dc94f90ef42e';
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
        $form['general_info_form[title]'] = str_repeat('a', 256);
        $form['general_info_form[identifier]'] = str_repeat('a', 61);

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit contenir au maximum 60 caractères.', $crawler->filter('#general_info_form_identifier_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit contenir au maximum 255 caractères.', $crawler->filter('#general_info_form_title_error')->text());
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

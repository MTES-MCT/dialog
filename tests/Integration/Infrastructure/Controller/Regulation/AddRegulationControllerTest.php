<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Domain\Regulation\Enum\RegulationOrderCategoryEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddRegulationControllerTest extends AbstractWebTestCase
{
    public function testAdd(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Nouvel arrêté', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Nouvel arrêté - DiaLog', $crawler);
        $this->assertSame('Informations générales', $crawler->filter('h3')->text());

        $this->assertSame('Brouillon', $crawler->filter('[data-testid="status-badge"]')->text());
        $this->assertSame('', $crawler->selectButton('Publier')->attr('disabled'));
        $this->assertSame('', $crawler->selectButton('Dupliquer')->attr('disabled'));
        $deleteLink = $crawler->selectLink('Supprimer');
        $this->assertSame('http://localhost/regulations', $deleteLink->link()->getUri());

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $this->assertSame('2023-05-10', $form->get('general_info_form[startDate]')->getValue()); // Init with tomorrow date
        $form['general_info_form[identifier]'] = 'F022023';
        $form['general_info_form[organization]'] = OrganizationFixture::MAIN_ORG_ID;
        $form['general_info_form[description]'] = 'Interdiction de circuler dans Paris';
        $form['general_info_form[startDate]'] = '2023-02-14';
        $form['general_info_form[category]'] = RegulationOrderCategoryEnum::OTHER->value;
        $form['general_info_form[otherCategoryText]'] = 'Trou en formation';
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulation_detail');
    }

    public function testEmptyData(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $form['general_info_form[category]'] = RegulationOrderCategoryEnum::OTHER->value;
        $form['general_info_form[otherCategoryText]'] = '';

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#general_info_form_otherCategoryText_error')->text());
    }

    public function testOtherCategoryTextMissing(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $form['general_info_form[startDate]'] = '';
        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#general_info_form_identifier_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#general_info_form_description_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#general_info_form_startDate_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide. Cette valeur doit être l\'un des choix proposés.', $crawler->filter('#general_info_form_category_error')->text());
    }

    public function testBadPeriod(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $form['general_info_form[identifier]'] = 'F022030';
        $form['general_info_form[organization]'] = OrganizationFixture::MAIN_ORG_ID;
        $form['general_info_form[description]'] = 'Travaux';
        $form['general_info_form[startDate]'] = '2023-02-12';
        $form['general_info_form[endDate]'] = '2023-02-11';
        $form['general_info_form[category]'] = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('La date de fin doit être après le 12/02/2023.', $crawler->filter('#general_info_form_endDate_error')->text());
    }

    public function testAddWithAnAlreadyExistingIdentifier(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $form['general_info_form[identifier]'] = 'FO1/2023';
        $form['general_info_form[organization]'] = OrganizationFixture::MAIN_ORG_ID;
        $form['general_info_form[description]'] = 'Travaux';
        $form['general_info_form[startDate]'] = '2023-02-12';
        $form['general_info_form[endDate]'] = '2024-02-11';
        $form['general_info_form[category]'] = RegulationOrderCategoryEnum::ROAD_MAINTENANCE->value;

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Un arrêté avec cet identifiant existe déjà. Veuillez saisir un autre identifiant.', $crawler->filter('#general_info_form_identifier_error')->text());
    }

    public function testCancel(): void
    {
        $client = $this->login();
        $client->request('GET', '/regulations/add');
        $this->assertResponseStatusCodeSame(200);

        $client->clickLink('Annuler');
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_regulations_list');
    }

    public function testFieldsTooLong(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '/regulations/add');
        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Continuer');
        $form = $saveButton->form();
        $form['general_info_form[identifier]'] = str_repeat('a', 61);
        $form['general_info_form[description]'] = str_repeat('a', 256);
        $form['general_info_form[otherCategoryText]'] = str_repeat('a', 101);

        $crawler = $client->submit($form);
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 60 caractères.', $crawler->filter('#general_info_form_identifier_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#general_info_form_description_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#general_info_form_otherCategoryText_error')->text());
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

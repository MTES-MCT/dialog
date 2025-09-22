<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\RegulationOrderTemplate;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddRegulationOrderTemplateControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/add');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Ajouter un modèle', $crawler->filter('h3')->text());
        $this->assertMetaTitle('Ajouter un modèle - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        // Other fields are pre-filled with default values.
        $values['regulation_order_template_form']['name'] = 'Restriction de vitesse';
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_regulation_order_templates_list');
    }

    public function testBadFormValues(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/add');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['regulation_order_template_form']['name'] = '';
        $values['regulation_order_template_form']['title'] = '';
        $values['regulation_order_template_form']['visaContent'] = '';
        $values['regulation_order_template_form']['consideringContent'] = '';
        $values['regulation_order_template_form']['articleContent'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#regulation_order_template_form_name_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#regulation_order_template_form_title_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#regulation_order_template_form_visaContent_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#regulation_order_template_form_consideringContent_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#regulation_order_template_form_articleContent_error')->text());

        $values = $form->getPhpValues();
        $values['regulation_order_template_form']['name'] = str_repeat('a', 151);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 150 caractères.', $crawler->filter('#regulation_order_template_form_name_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_USER_EMAIL);
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/add');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login(UserFixture::OTHER_ORG_USER_EMAIL);
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/regulation_order_templates/add');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/regulation_order_templates/add');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\RegulationOrderTemplate;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class EditRegulationOrderTemplateControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/54eacea0-e1e0-4823-828d-3eae72b76da8/edit');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Réglementation de vitesse en agglomération', $crawler->filter('h3')->text());
        $this->assertMetaTitle('Réglementation de vitesse en agglomération - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['regulation_order_template_form']['name'] = 'Nouveau modèle d\'arrêté';
        $values['regulation_order_template_form']['title'] = 'Arrete temporaire n°[numero_arrete]';
        $values['regulation_order_template_form']['visaContent'] = 'VU ...';
        $values['regulation_order_template_form']['consideringContent'] = 'CONSIDERANT ...';
        $values['regulation_order_template_form']['articleContent'] = 'ARTICLES ...';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_regulation_order_templates_list');
    }

    public function testBadFormValues(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/54eacea0-e1e0-4823-828d-3eae72b76da8/edit');

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

        $values['regulation_order_template_form']['name'] = str_repeat('a', 151);

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 150 caractères.', $crawler->filter('#regulation_order_template_form_name_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/29488f0c-7b16-4d6c-82e3-f395102c32c2/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/regulation_order_templates/29488f0c-7b16-4d6c-82e3-f395102c32c2/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/regulation_order_templates/29488f0c-7b16-4d6c-82e3-f395102c32c2/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/regulation_order_templates/29488f0c-7b16-4d6c-82e3-f395102c32c2/edit');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\MailingList;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddRecipientsListControllerTest extends AbstractWebTestCase
{
    public function testIndex(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/add');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Ajouter un destinataire', $crawler->filter('h3')->text());
        $this->assertMetaTitle('Ajouter un destinataire - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['mailing_list_form']['name'] = 'Isabelle Truc';
        $values['mailing_list_form']['email'] = 'isabelle@beta.fr';
        $values['mailing_list_form']['role'] = 'Prefecture';

        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_recipients_list');
    }

    public function testBadFormValues(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/add');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['mailing_list_form']['name'] = '';
        $values['mailing_list_form']['email'] = '';
        $values['mailing_list_form']['role'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#mailing_list_form_name_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#mailing_list_form_email_error')->text());
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/recipients/add');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/recipients/add');
        $this->assertResponseStatusCodeSame(403);
    }
}

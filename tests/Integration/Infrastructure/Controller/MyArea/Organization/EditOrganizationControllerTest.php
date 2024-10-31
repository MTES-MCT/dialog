<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class EditOrganizationControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID_2 . '/edit');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Modifier mon organisation', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Modifier mon organisation - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['organization_form']['name'] = 'Main Org';
        $values['organization_form']['siret'] = '12345678909812';
        $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_my_area_organization_detail');
    }

    public function testBadFormValues(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID_2 . '/edit');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['organization_form']['name'] = '';
        $values['organization_form']['siret'] = '';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#organization_form_name_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#organization_form_siret_error')->text());

        $values['organization_form']['siret'] = 'abc';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne doit avoir exactement 14 caractères.', $crawler->filter('#organization_form_siret_error')->text());
    }

    public function testSiretAlreadyExist(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID_2 . '/edit');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['organization_form']['name'] = 'Main Org';
        $values['organization_form']['siret'] = '12345678909876';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Ce SIRET est déjà utilisé.', $crawler->filter('#organization_form_siret_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testEditDialog(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/edit');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

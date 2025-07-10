<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\SigningAuthority\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class SaveSigningAuthorityFragmentControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/signing_authority/save');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['signing_authority_form']['name'] = 'Madame la maire de Pau';
        $values['signing_authority_form']['role'] = 'Adjoint au maire';
        $values['signing_authority_form']['signatoryName'] = 'Madame X';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_organizations_signing_authority_preview');
    }

    public function testBadFormValues(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/signing_authority/save');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['signing_authority_form']['name'] = '';
        $values['signing_authority_form']['role'] = '';
        $values['signing_authority_form']['signatoryName'] = '';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#signing_authority_form_name_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#signing_authority_form_role_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#signing_authority_form_signatoryName_error')->text());

        $values['signing_authority_form']['name'] = str_repeat('a', 101);
        $values['signing_authority_form']['role'] = str_repeat('a', 101);
        $values['signing_authority_form']['signatoryName'] = str_repeat('a', 101);
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#signing_authority_form_name_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#signing_authority_form_role_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#signing_authority_form_signatoryName_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/signing_authority/save');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::REGION_IDF_ID . '/signing_authority/save');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/signing_authority/save');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/signing_authority/save');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

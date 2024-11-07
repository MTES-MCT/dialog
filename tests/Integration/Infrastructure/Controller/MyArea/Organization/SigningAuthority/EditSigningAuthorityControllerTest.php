<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\SigningAuthority;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class EditSigningAuthorityControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/signing_authority/edit');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Autorité signataire', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Autorité signataire - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['signing_authority_form']['name'] = 'Madame la maire de Pau';
        $values['signing_authority_form']['address'] = '3 rue de la Concertation';
        $values['signing_authority_form']['signatoryName'] = 'Madame X, maire de Pau';
        $values['signing_authority_form']['placeOfSignature'] = 'Savenay';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_signing_authority_edit');
    }

    public function testBadFormValues(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/signing_authority/edit');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['signing_authority_form']['name'] = '';
        $values['signing_authority_form']['address'] = '';
        $values['signing_authority_form']['signatoryName'] = '';
        $values['signing_authority_form']['placeOfSignature'] = '';
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#signing_authority_form_name_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#signing_authority_form_address_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#signing_authority_form_placeOfSignature_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#signing_authority_form_signatoryName_error')->text());

        $values['signing_authority_form']['name'] = str_repeat('a', 101);
        $values['signing_authority_form']['signatoryName'] = str_repeat('a', 101);
        $values['signing_authority_form']['placeOfSignature'] = str_repeat('a', 101);
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#signing_authority_form_name_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#signing_authority_form_placeOfSignature_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 100 caractères.', $crawler->filter('#signing_authority_form_signatoryName_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/signing_authority/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::OTHER_ORG_ID . '/signing_authority/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/signing_authority/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::MAIN_ORG_ID . '/signing_authority/edit');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

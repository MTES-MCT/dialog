<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class SaveOrganizationDetailFragmentControllerTest extends AbstractWebTestCase
{
    public function testEdit(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/save');

        $this->assertResponseStatusCodeSame(200);

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../../../fixtures/logo.png',
            'logo.png',
        );

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['organization_form']['name'] = 'Département de Seine-Saint-Denis';
        $values['organization_form']['address'] = '123 Rue de la Paix';
        $values['organization_form']['zipCode'] = '75000';
        $values['organization_form']['city'] = 'Paris';
        $values['organization_form']['addressComplement'] = 'Appartement 1';
        $values['organization_form']['file'] = $uploadedFile;

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $client->followRedirect();

        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('fragment_organizations_preview');
    }

    public function testBadFormValues(): void
    {
        $client = $this->login('mathieu.fernandez@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/save');

        $saveButton = $crawler->selectButton('Sauvegarder');
        $form = $saveButton->form();

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../../../fixtures/dialog_export.docx',
            'dialog_export.docx',
        );

        // Get the raw values.
        $values = $form->getPhpValues();
        $values['organization_form']['name'] = '';
        $values['organization_form']['address'] = '';
        $values['organization_form']['zipCode'] = '';
        $values['organization_form']['city'] = '';
        $values['organization_form']['file'] = $uploadedFile;
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#organization_form_name_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#organization_form_address_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#organization_form_zipCode_error')->text());
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#organization_form_city_error')->text());

        $values['organization_form']['name'] = str_repeat('a', 256);
        $values['organization_form']['address'] = str_repeat('a', 256);
        $values['organization_form']['zipCode'] = str_repeat('a', 256);
        $values['organization_form']['city'] = str_repeat('a', 256);
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#organization_form_name_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#organization_form_address_error')->text());
        $this->assertSame('Cette chaîne doit avoir exactement 5 caractères.', $crawler->filter('#organization_form_zipCode_error')->text());
        $this->assertSame('Cette chaîne est trop longue. Elle doit avoir au maximum 255 caractères.', $crawler->filter('#organization_form_city_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/save');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::REGION_IDF_ID . '/save');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/_fragment/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/save');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/_fragment/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/save');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

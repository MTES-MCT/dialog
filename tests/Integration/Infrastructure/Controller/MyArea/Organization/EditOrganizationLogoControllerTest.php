<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\MyArea\Organization;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class EditOrganizationLogoControllerTest extends AbstractWebTestCase
{
    public function testEditLogo(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SAINT_OUEN_ID . '/logo/edit');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Logo', $crawler->filter('h2')->text());
        $this->assertMetaTitle('Logo - DiaLog', $crawler);

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../../fixtures/logo.png',
            'logo.png',
        );

        $saveButton = $crawler->selectButton('Ajouter un logo');
        $form = $saveButton->form();
        $form['logo_form[file]'] = $uploadedFile;
        $client->submit($form);

        $this->assertResponseStatusCodeSame(303);
        $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_config_organization_edit_logo');
    }

    public function testBadFileExtension(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SAINT_OUEN_ID . '/logo/edit');

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../../fixtures/dialog_export.docx',
            'dialog_export.docx',
        );

        $saveButton = $crawler->selectButton('Ajouter un logo');
        $form = $saveButton->form();
        $form['logo_form[file]'] = $uploadedFile;
        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('L\'extension du fichier est invalide ("docx"). Les extensions autorisées sont "jpg", "jpeg", "webp", "png", "svg".', $crawler->filter('#logo_form_file_error')->text());
    }

    public function testFileTooLarge(): void
    {
        $client = $this->login('florimond.manca@beta.gouv.fr');
        $crawler = $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SAINT_OUEN_ID . '/logo/edit');

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../../fixtures/file_too_large.pdf',
            'file_too_large.pdf',
        );

        $saveButton = $crawler->selectButton('Ajouter un logo');
        $form = $saveButton->form();
        $form['logo_form[file]'] = $uploadedFile;
        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Le fichier est trop volumineux (398.02 kB). Sa taille ne doit pas dépasser 300 kB.', $crawler->filter('#logo_form_file_error')->text());
    }

    public function testNotAdministrator(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/logo/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationNotOwned(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::REGION_IDF_ID . '/logo/edit');
        $this->assertResponseStatusCodeSame(403);
    }

    public function testOrganizationOrUserNotFound(): void
    {
        $client = $this->login();
        $client->request('GET', '/mon-espace/organizations/f5c1cea8-a61d-43a7-9b5d-4b8c9557c673/logo/edit');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/mon-espace/organizations/' . OrganizationFixture::SEINE_SAINT_DENIS_ID . '/logo/edit');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

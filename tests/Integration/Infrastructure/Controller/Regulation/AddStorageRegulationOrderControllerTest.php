<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class AddStorageRegulationOrderControllerTest extends AbstractWebTestCase
{
    public function testAddStorageRegulationOrder(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_PUBLISHED . '/storage/add');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../fixtures/file_too_large.pdf',
            'file_too_large.pdf',
        );
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $form['storage_regulation_order_form[file]'] = $uploadedFile;
        $form['storage_regulation_order_form[title]'] = 'Test';
        $form['storage_regulation_order_form[url]'] = 'https://example.com/storage1.pdf';
        $client->submit($form);

        $this->assertResponseStatusCodeSame(200);
        $streams = $crawler->filter('turbo-stream');

        $this->assertSame($streams->eq(0)->attr('action'), 'update');
        $this->assertSame($streams->eq(0)->attr('target'), 'upload-form-frame');
    }

    public function testBadFormValues(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/storage/add');

        $uploadedFile = new UploadedFile(
            __DIR__ . '/../../../../fixtures/aires_de_stockage_test.csv',
            'aires_de_stockage_test.csv',
        );
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();

        $values = $form->getPhpValues();
        $values['storage_regulation_order_form']['title'] = '';
        $values['storage_regulation_order_form']['file'] = $uploadedFile;
        $values['storage_regulation_order_form']['url'] = 'example.com/storage1.pdf';

        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Cette valeur ne doit pas être vide.', $crawler->filter('#storage_regulation_order_form_title_error')->text());
        $this->assertSame('Cette valeur n\'est pas une URL valide.', $crawler->filter('#storage_regulation_order_form_url_error')->text());
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/storage/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

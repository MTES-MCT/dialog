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
        $saveButton = $crawler->selectButton('Ajouter');
        $form = $saveButton->form();
        $form['storage_regulation_order_form[file]'] = $uploadedFile;
        $form['storage_regulation_order_form[title]'] = 'Test';
        $form['storage_regulation_order_form[url]'] = 'https://example.com/storage1.pdf';
        $client->submit($form);

        $this->assertResponseStatusCodeSame(303);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/storage/add');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

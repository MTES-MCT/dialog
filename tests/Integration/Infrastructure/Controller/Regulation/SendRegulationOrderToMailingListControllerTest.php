<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\UserFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class SendRegulationOrderToMailingListControllerTest extends AbstractWebTestCase
{
    public function testSendEmails(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/mailing_list_share');

        $this->assertSecurityHeaders();
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('Partager l\'arrêté', $crawler->filter('h4')->text());
        $this->assertMetaTitle('Partager l\'arrêté - DiaLog', $crawler);

        $saveButton = $crawler->selectButton('Partager');
        $form = $saveButton->form();
        $form['send_to_mailing_list_form[emails]'] = 'isa.dufour@yahoo.fr , toto@gmail.com';
        $client->submit($form);
        $this->assertResponseStatusCodeSame(303);

        $this->assertEmailCount(2);

        $crawler = $client->followRedirect();
        $this->assertResponseStatusCodeSame(200);
        $this->assertRouteSame('app_mailing_list_share');
        $this->assertSame('Emails de partage envoyés', $crawler->filter('h3')->text());
    }

    public function testBadValues(): void
    {
        $client = $this->login(UserFixture::DEPARTMENT_93_ADMIN_EMAIL);
        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/mailing_list_share');

        $saveButton = $crawler->selectButton('Partager');
        $form = $saveButton->form();
        $form['send_to_mailing_list_form[emails]'] = 'isa';

        $crawler = $client->submit($form);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('isa n\'est pas une adresse email valide.', $crawler->filter('#send_to_mailing_list_form_emails_error')->text());
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class AddMeasureLinkFragmentControllerTest extends AbstractWebTestCase
{
    public function testLink(): void
    {
        $client = $this->login();
        $crawler = $client->request('GET', '_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add-link');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $saveButton = $crawler->selectButton('Ajouter une mesure');
        $form = $saveButton->form();
        $this->assertSame('http://localhost/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add', $form->getUri());
    }

    public function testBadUuid(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/regulations/aaaaaaaa/measure/add-link');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/add-link');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

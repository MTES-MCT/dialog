<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DuplicateMeasureFragmentControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    public function testDuplicate(): void
    {
        $client = $this->login();

        $crawler = $client->request('POST', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/duplicate',[
            '_token' => $this->generateCsrfToken($client, 'duplicate-measure'),
        ]);

        $this->assertResponseStatusCodeSame(200);
        $streams = $crawler->filter('turbo-stream');

        $this->assertSame($streams->eq(0)->attr('action'), 'append');
        $this->assertSame($streams->eq(0)->attr('target'), 'measure_list');
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/duplicate');

        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

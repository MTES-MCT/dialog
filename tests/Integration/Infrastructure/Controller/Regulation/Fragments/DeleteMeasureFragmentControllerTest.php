<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;

final class DeleteMeasureFragmentControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    private function countRows($crawler): int
    {
        return $crawler->filter('#measure_list > li')->count();
    }

    public function testDelete(): void
    {
        $client = $this->login();

        $crawler = $client->request('GET', '/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL);
        $this->assertSame(2, $this->countRows($crawler));

        $crawler = $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-measure'),
        ]);
        $streams = $crawler->filter('turbo-stream');

        $this->assertSame($streams->eq(0)->attr('action'), 'replace');
        $this->assertSame($streams->eq(0)->attr('target'), 'measure_0658d836-de22-75f2-8000-bb36c98113a5_delete_button');
        $this->assertSame($streams->eq(1)->attr('target'), 'block_measure_' . MeasureFixture::UUID_TYPICAL);
        $this->assertSame($streams->eq(1)->attr('action'), 'remove');

        $crawler = $client->request('GET', sprintf('/regulations/%s', RegulationOrderRecordFixture::UUID_TYPICAL));
        $this->assertSame(1, $this->countRows($crawler));
    }

    public function testMeasureCannotBeDeleted(): void
    {
        $client = $this->login();

        $crawler = $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_PERMANENT . '/measure/' . MeasureFixture::UUID_PERMANENT_ONLY_ONE . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-measure'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testMeasureNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_DOES_NOT_EXIST . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-measure'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testRegulationOrderRecordNotFound(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_DOES_NOT_EXIST . '/measure/' . MeasureFixture::UUID_TYPICAL . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-measure'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testInvalidCsrfToken(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/delete');
        $this->assertResponseStatusCodeSame(400);
    }

    public function testBadRegulationOrderUuid(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/aaa/measure/' . MeasureFixture::UUID_TYPICAL . '/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-measure'),
        ]);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testBadMeasureUuid(): void
    {
        $client = $this->login();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/aaa/delete', [
            'token' => $this->generateCsrfToken($client, 'delete-measure'),
        ]);
        $this->assertResponseStatusCodeSame(404);
    }

    public function testWithoutAuthenticatedUser(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_TYPICAL . '/measure/' . MeasureFixture::UUID_TYPICAL . '/delete');
        $this->assertResponseRedirects('http://localhost/login', 302);
    }
}

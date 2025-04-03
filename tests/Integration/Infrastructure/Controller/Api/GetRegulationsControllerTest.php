<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderRecordFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\StorageAreaFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

final class GetRegulationsControllerTest extends AbstractWebTestCase
{
    use SessionHelper;

    private function prepareWinterMaintenanceRegulationOrder(KernelBrowser $client): void
    {
        // Add storage area
        $crawler = $client->request('GET', '/_fragment/regulations/' . RegulationOrderRecordFixture::UUID_WINTER_MAINTENANCE . '/measure/' . MeasureFixture::UUID_WINTER_MAINTENANCE . '/form');
        $this->assertResponseStatusCodeSame(200);
        $saveButton = $crawler->selectButton('Valider');
        $form = $saveButton->form();
        $values = $form->getPhpValues();
        $values['measure_form']['locations'][0]['nationalRoad']['storageArea'] = StorageAreaFixture::UUID_DIRO_N176;
        $crawler = $client->request($form->getMethod(), $form->getUri(), $values, $form->getPhpFiles());
        $this->assertResponseStatusCodeSame(303);

        // Publish
        $client->request('POST', '/regulations/' . RegulationOrderRecordFixture::UUID_WINTER_MAINTENANCE . '/publish', [
            '_token' => $this->generateCsrfToken($client, 'publish-regulation'),
        ]);
        $this->assertResponseStatusCodeSame(303);
    }

    public function testGetRegulationsToDatexFormat(): void
    {
        $client = $this->login();

        // Prepare some regulation orders to avoid the need to have published versions of fixtures
        $this->prepareWinterMaintenanceRegulationOrder($client);

        $client->request('GET', '/api/regulations.xml');
        $response = $client->getResponse();

        $this->assertSame('text/xml; charset=UTF-8', $response->headers->get('content-type'));
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();

        $xml = new \DOMDocument();
        $xml->loadXML($response->getContent(), \LIBXML_NOBLANKS);
        $this->assertTrue($xml->schemaValidate(self::$kernel->getProjectDir() . '/docs/spec/datex2/DATEXII_3_D2Payload.xsd'));

        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/get-regulations-expected-result.xml',
            $response->getContent(),
        );
    }
}

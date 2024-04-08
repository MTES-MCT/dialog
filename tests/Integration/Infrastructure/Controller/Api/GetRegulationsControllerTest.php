<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetRegulationsControllerTest extends AbstractWebTestCase
{
    public function testGetRegulationsToDatexFormat(): void
    {
        $client = static::createClient();
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

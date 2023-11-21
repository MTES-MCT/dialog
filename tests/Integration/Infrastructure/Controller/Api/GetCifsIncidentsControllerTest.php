<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetCifsIncidentsControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/regulations/cifs.xml');
        $response = $client->getResponse();

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('text/xml; charset=UTF-8', $response->headers->get('content-type'));

        $xml = new \DOMDocument();
        $xml->loadXML($response->getContent(), \LIBXML_NOBLANKS);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/cifs-incidents-expected-result.xml',
            $response->getContent(),
        );
        $this->assertTrue($xml->schemaValidate(self::$kernel->getProjectDir() . '/docs/spec/cifs/cifsv2.xsd'));
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GetCifsIncidentsControllerTest extends AbstractWebTestCase
{
    public function testGet(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/regulations/cifs.xml');
        $response = $client->getResponse();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertSame('text/xml; charset=UTF-8', $response->headers->get('content-type'));

        $content = $client->getInternalResponse()->getContent();
        $xml = new \DOMDocument();
        $xml->loadXML($content, \LIBXML_NOBLANKS);
        $this->assertXmlStringEqualsXmlFile(
            __DIR__ . '/cifs-incidents-expected-result.xml',
            $content,
        );
        $this->assertTrue($xml->schemaValidate(self::$kernel->getProjectDir() . '/docs/spec/cifs/cifsv2.xsd'));
    }
}

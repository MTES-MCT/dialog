<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\RegulationOrderFixture;
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

    public function testGetSplitsDisjointLinesIntoSeparateIncidents(): void
    {
        $client = static::createClient();

        // L'emprise de la départementale devient deux lignes disjointes, distantes de plusieurs kilomètres.
        $line1 = '48.254442215 -3.014686584 48.25302725 -3.013954845 48.24834206 -3.010991824';
        $line2 = '48.278001167 -2.963690776 48.276629108 -2.964337836 48.270709216 -2.966538894';
        static::getContainer()->get('doctrine')->getConnection()->executeStatement(
            'UPDATE location SET geometry = ST_GeomFromGeoJSON(:geometry) WHERE uuid = :uuid',
            [
                'geometry' => '{"type":"MultiLineString","coordinates":['
                    . '[[-3.014686584,48.254442215],[-3.013954845,48.25302725],[-3.010991824,48.24834206]],'
                    . '[[-2.963690776,48.278001167],[-2.964337836,48.276629108],[-2.966538894,48.270709216]]'
                    . ']}',
                'uuid' => LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD,
            ],
        );

        $client->request('GET', '/api/regulations/cifs.xml');
        $this->assertResponseStatusCodeSame(200);

        $content = $client->getInternalResponse()->getContent();
        $xml = new \DOMDocument();
        $xml->loadXML($content, \LIBXML_NOBLANKS);
        $this->assertTrue($xml->schemaValidate(self::$kernel->getProjectDir() . '/docs/spec/cifs/cifsv2.xsd'));

        $ids = [];
        $polylinesById = [];

        foreach ($xml->getElementsByTagName('incident') as $incident) {
            $id = $incident->getAttribute('id');
            $ids[] = $id;

            if (str_contains($id, LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD)) {
                $polylinesById[$id] = $incident->getElementsByTagName('polyline')->item(0)->textContent;
            }
        }

        // Les ID restent uniques dans le flux.
        $this->assertSame($ids, array_unique($ids));

        // Un incident par ligne et par période (3 périodes), jamais les deux lignes dans la même polyline.
        $prefix = RegulationOrderFixture::IDENTIFIER_CIFS . ':' . LocationFixture::UUID_CIFS_DEPARTMENTAL_ROAD . ':';
        ksort($polylinesById);
        $this->assertSame(
            [
                $prefix . '06548fe3-7bfb-73af-8000-f7f34af31312:1' => $line1,
                $prefix . '06548fe3-7bfb-73af-8000-f7f34af31312:2' => $line2,
                $prefix . '0654b639-cd33-7507-8000-e2ea21673135:1' => $line1,
                $prefix . '0654b639-cd33-7507-8000-e2ea21673135:2' => $line2,
                $prefix . '0654b63a-838d-798b-8000-044b619f225d:1' => $line1,
                $prefix . '0654b63a-838d-798b-8000-044b619f225d:2' => $line2,
            ],
            $polylinesById,
        );
    }
}

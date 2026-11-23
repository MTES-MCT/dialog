<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Domain\Regulation\Enum\DirectionEnum;
use App\Domain\Regulation\Enum\MeasureTypeEnum;
use App\Infrastructure\Persistence\Doctrine\Fixtures\LocationFixture;
use App\Infrastructure\Persistence\Doctrine\Fixtures\MeasureFixture;
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

    public function testOneWayClosesTheOppositeDirection(): void
    {
        $client = static::createClient();
        $connection = static::getContainer()->get('doctrine.dbal.default_connection');

        // La mesure CIFS des fixtures devient un sens unique : circulation autorisée de A vers B
        // sur la Rue Claude Monet, la D14 de la même mesure restant en double sens.
        $connection->executeStatement('UPDATE measure SET type = :type WHERE uuid = :uuid', [
            'type' => MeasureTypeEnum::ONE_WAY->value,
            'uuid' => MeasureFixture::UUID_CIFS,
        ]);
        $connection->executeStatement('UPDATE named_street SET direction = :direction WHERE location_uuid = :uuid', [
            'direction' => DirectionEnum::A_TO_B->value,
            'uuid' => LocationFixture::UUID_CIFS_NAMED_STREET,
        ]);

        $client->request('GET', '/api/regulations/cifs.xml');
        $this->assertResponseStatusCodeSame(200);

        $xml = new \DOMDocument();
        $xml->loadXML($client->getInternalResponse()->getContent(), \LIBXML_NOBLANKS);
        $this->assertTrue($xml->schemaValidate(self::$kernel->getProjectDir() . '/docs/spec/cifs/cifsv2.xsd'));

        $xpath = new \DOMXPath($xml);
        $incidents = $xpath->query('//incident[starts-with(@id, "F/CIFS/2023:")]');

        // La D14, en double sens, n'a pas de sens à fermer : seule la Rue Claude Monet est exportée
        $this->assertCount(1, $incidents);
        $incident = $incidents->item(0);
        $this->assertStringContainsString(LocationFixture::UUID_CIFS_NAMED_STREET, $incident->getAttribute('id'));
        $this->assertSame('ROAD_CLOSED', $xpath->evaluate('string(type)', $incident));
        $this->assertSame('Sens unique', $xpath->evaluate('string(description)', $incident));
        $this->assertSame('ONE_DIRECTION', $xpath->evaluate('string(direction)', $incident));
        // Polyligne parcourue de B vers A, le sens fermé : l'inverse de celle de la fermeture en double sens
        $this->assertSame(
            '48.911316524 2.339535452 48.911532934 2.339150682 48.911546991 2.339090471 48.911549433 2.339044051 48.911545625 2.339005897 48.911521022 2.338947542 48.911459354 2.338854183 48.910695641 2.337678265 48.910599408 2.337514404',
            $xpath->evaluate('string(polyline)', $incident),
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Regulation\Fragments;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class GetLocationGeometryControllerTest extends AbstractWebTestCase
{
    // Périmètre englobant la Rue Ardoin à Saint-Ouen-sur-Seine (93070)
    private const ZONE_GEOMETRY = '{"type":"Polygon","coordinates":[[[2.3215,48.9075],[2.331,48.9075],[2.331,48.916],[2.3215,48.916],[2.3215,48.9075]]]}';

    public function testZonePreview(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/location-geometry?' . http_build_query([
            'roadType' => 'zone',
            'geometry' => self::ZONE_GEOMETRY,
        ]));

        $this->assertResponseStatusCodeSame(200);

        $sections = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('MultiLineString', $sections['type']);
        $this->assertNotEmpty($sections['coordinates']);

        // Rue Ardoin soustraite de l'aperçu (voie entière en exception) : le tracé change.
        $client->request('GET', '/_fragment/location-geometry?' . http_build_query([
            'roadType' => 'zone',
            'geometry' => self::ZONE_GEOMETRY,
            'excludedRoadBanIds' => '93070_0074',
        ]));

        $this->assertResponseStatusCodeSame(200);

        $withoutArdoin = json_decode($client->getResponse()->getContent(), true);
        $this->assertNotEmpty($withoutArdoin['type']);
        $this->assertNotSame($sections, $withoutArdoin);
    }

    public function testZonePreviewWithoutGeometry(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/location-geometry?roadType=zone');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testZonePreviewWithInvalidGeometry(): void
    {
        $client = $this->login();
        $client->request('GET', '/_fragment/location-geometry?' . http_build_query([
            'roadType' => 'zone',
            'geometry' => 'pas du JSON',
        ]));

        $this->assertResponseStatusCodeSame(204);
    }
}

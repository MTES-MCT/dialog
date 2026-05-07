<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Map\Fragment;

use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class MapTilesControllerTest extends AbstractWebTestCase
{
    // Tile z=10/x=518/y=352 covers Paris where most location fixtures lie.
    private const PARIS_TILE = '10/518/352';

    public function testReturnsMvtTileForParis(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/tiles/' . self::PARIS_TILE . '.mvt');

        $this->assertResponseStatusCodeSame(200);
        $this->assertSecurityHeaders();
        $this->assertResponseHeaderSame('Content-Type', 'application/vnd.mapbox-vector-tile');
        $this->assertResponseHeaderSame('Cache-Control', 'max-age=300, public');

        // MVT is a non-empty binary blob.
        $this->assertNotSame('', $client->getResponse()->getContent());
    }

    public function testReturnsNoContentWhenTileHasNoFeatures(): void
    {
        $client = static::createClient();
        // A tile in the middle of the ocean, far from any test fixture geometry.
        $client->request('GET', '/carte/tiles/10/0/0.mvt');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testReturnsNoContentBeyondMaxZoom(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/tiles/23/0/0.mvt');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testReturnsNoContentForOutOfRangeCoordinates(): void
    {
        $client = static::createClient();
        // At z=1 the maximum index is 1: x=2 is out of range.
        $client->request('GET', '/carte/tiles/1/2/0.mvt');

        $this->assertResponseStatusCodeSame(204);
    }

    public function testRespectsMeasureTypesFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/tiles/' . self::PARIS_TILE . '.mvt?map_filter_form[measureTypes][]=noEntry&map_filter_form[displayPermanentRegulations]=yes&map_filter_form[displayTemporaryRegulations]=yes');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/vnd.mapbox-vector-tile');
        $this->assertNotSame('', $client->getResponse()->getContent());
    }

    public function testRespectsMeasureDatesFilter(): void
    {
        $client = static::createClient();
        $client->request('GET', '/carte/tiles/' . self::PARIS_TILE . '.mvt?map_filter_form[measureTypes][]=noEntry&map_filter_form[measureTypes][]=speedLimitation&map_filter_form[displayTemporaryRegulations]=yes&map_filter_form[startDate]=2023-06-02&map_filter_form[endDate]=2023-06-06');

        $this->assertResponseStatusCodeSame(200);
        $this->assertResponseHeaderSame('Content-Type', 'application/vnd.mapbox-vector-tile');
    }
}

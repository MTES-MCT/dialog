<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api;

use App\Infrastructure\Persistence\Doctrine\Fixtures\OrganizationFixture;
use App\Tests\Integration\Infrastructure\Controller\AbstractWebTestCase;

final class CheckLocationControllerTest extends AbstractWebTestCase
{
    private function check(array $payload, array $headers = []): array
    {
        $client = static::createClient();
        $client->request(
            'POST',
            '/api/locations/check',
            [],
            [],
            $headers + [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_CLIENT_ID' => 'clientId',
                'HTTP_X_CLIENT_SECRET' => 'clientSecret',
            ],
            json_encode($payload),
        );

        return json_decode($client->getResponse()->getContent(), true);
    }

    public function testAcceptedLane(): void
    {
        $data = $this->check([
            'roadType' => 'lane',
            'namedStreet' => [
                'cityCode' => '93070',
                'cityLabel' => 'saint ouen sur seine',
                'roadName' => 'rue eugène berthoud',
                'direction' => 'BOTH',
            ],
        ]);

        $this->assertResponseStatusCodeSame(200);
        $this->assertTrue($data['accepted']);
        $this->assertIsArray($data['geometry']);
        $this->assertArrayHasKey('type', $data['geometry']);
    }

    public function testLaneOutOfOrganizationPerimeter(): void
    {
        $data = $this->check([
            'roadType' => 'lane',
            'namedStreet' => [
                'cityCode' => '44195',
                'cityLabel' => 'Savenay (44260)',
                'roadName' => 'Route du Grand Brossais',
                'fromHouseNumber' => '15',
                'toHouseNumber' => '37bis',
                'direction' => 'BOTH',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(400, $data['status']);
        $this->assertFalse($data['accepted']);
        $this->assertSame('organization_cannot_intervene_on_geometry', $data['reason']);
        $this->assertStringStartsWith('L\'organisation "' . OrganizationFixture::SEINE_SAINT_DENIS_NAME . '" ne semble pas avoir les compétences', $data['detail']);
        $this->assertArrayNotHasKey('geometry', $data);
    }

    public function testLaneGeocodingFailure(): void
    {
        $data = $this->check([
            'roadType' => 'lane',
            'namedStreet' => [
                'cityCode' => '59368',
                'cityLabel' => 'La Madeleine (59110)',
                'roadName' => 'Rue inconnue',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(400, $data['status']);
        $this->assertFalse($data['accepted']);
        $this->assertSame('geocoding_failed', $data['reason']);
        $this->assertStringStartsWith('Cette adresse n’est pas reconnue.', $data['detail']);
    }

    public function testZoneWithoutStreets(): void
    {
        $data = $this->check([
            'roadType' => 'zone',
            'zone' => [
                'label' => 'Zone sans rue',
                'geometry' => '{"type":"Polygon","coordinates":[[[2.4013,48.943],[2.4027,48.943],[2.4027,48.944],[2.4013,48.944],[2.4013,48.943]]]}',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(400, $data['status']);
        $this->assertFalse($data['accepted']);
        $this->assertSame('zone_without_streets', $data['reason']);
    }

    public function testAbscissaOutOfRange(): void
    {
        $data = $this->check([
            'roadType' => 'departmentalRoad',
            'departmentalRoad' => [
                'administrator' => 'Ardèche',
                'roadNumber' => 'D906',
                'fromPointNumber' => '34',
                'fromSide' => 'U',
                'fromAbscissa' => 0,
                'toPointNumber' => '34',
                'toSide' => 'U',
                'toAbscissa' => 900000,
                'direction' => 'BOTH',
            ],
        ]);

        $this->assertResponseStatusCodeSame(400);
        $this->assertFalse($data['accepted']);
        $this->assertSame('abscissa_out_of_range', $data['reason']);
    }

    private function provideIncompleteLocations(): array
    {
        $numberedRoad = [
            'administrator' => 'Ardèche',
            'roadNumber' => 'D906',
            'fromPointNumber' => '34',
            'fromSide' => 'U',
            'toPointNumber' => '34',
            'toSide' => 'U',
            'direction' => 'BOTH',
        ];

        return [
            'numbered-road-without-sides' => [
                ['roadType' => 'departmentalRoad', 'departmentalRoad' => ['fromSide' => null, 'toSide' => null] + $numberedRoad],
                ['location.departmentalRoad.fromSide', 'location.departmentalRoad.toSide'],
            ],
            'negative-abscissa' => [
                ['roadType' => 'departmentalRoad', 'departmentalRoad' => ['fromAbscissa' => -10] + $numberedRoad],
                ['location.departmentalRoad.fromAbscissa'],
            ],
            'lane-without-end' => [
                ['roadType' => 'lane', 'namedStreet' => [
                    'cityCode' => '93070',
                    'cityLabel' => 'saint ouen sur seine',
                    'roadName' => 'rue eugène berthoud',
                    'fromPointType' => 'houseNumber',
                    'fromHouseNumber' => '10',
                ]],
                ['location.namedStreet.toPointType'],
            ],
            'zone-not-polygon' => [
                ['roadType' => 'zone', 'zone' => [
                    'label' => 'Zone',
                    'geometry' => '{"type":"LineString","coordinates":[[2.33,48.9],[2.34,48.91]]}',
                ]],
                ['location.zone.geometry'],
            ],
        ];
    }

    /**
     * @dataProvider provideIncompleteLocations
     */
    public function testIncompleteLocation(array $payload, array $propertyPaths): void
    {
        $data = $this->check($payload);

        $this->assertResponseStatusCodeSame(422);
        $this->assertEqualsCanonicalizing($propertyPaths, array_column($data['violations'], 'propertyPath'));
    }

    public function testValidationErrors(): void
    {
        $data = $this->check(['roadType' => 'lane']);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSame('Validation failed', $data['detail']);
        $this->assertNotEmpty($data['violations']);
    }

    public function testInvalidCredentials(): void
    {
        $this->check(['roadType' => 'lane'], ['HTTP_X_CLIENT_SECRET' => 'wrong']);

        $this->assertResponseStatusCodeSame(401);
    }
}

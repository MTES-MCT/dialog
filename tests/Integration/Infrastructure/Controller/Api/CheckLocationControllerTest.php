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
        $this->assertNull($data['reason']);
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

        $this->assertResponseStatusCodeSame(200);
        $this->assertFalse($data['accepted']);
        $this->assertSame('organization_cannot_intervene_on_geometry', $data['reason']);
        $this->assertStringStartsWith('L\'organisation "' . OrganizationFixture::SEINE_SAINT_DENIS_NAME . '" ne semble pas avoir les compétences', $data['detail']);
        $this->assertNull($data['geometry']);
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

        $this->assertResponseStatusCodeSame(200);
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

        $this->assertResponseStatusCodeSame(200);
        $this->assertFalse($data['accepted']);
        $this->assertSame('zone_without_streets', $data['reason']);
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

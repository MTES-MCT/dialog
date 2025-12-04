<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Controller\Api\Statistics;

use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GetOrganizationsGeometriesControllerTest extends WebTestCase
{
    use SessionHelper;

    public function testGetOrganizationsGeometries(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/stats');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);

        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('type', $data);
        $this->assertSame('FeatureCollection', $data['type']);
        $this->assertArrayHasKey('features', $data);
        $this->assertIsArray($data['features']);

        $feature = $data['features'][0];

        $this->assertArrayHasKey('type', $feature);
        $this->assertSame('Feature', $feature['type']);
        $this->assertArrayHasKey('geometry', $feature);
        $this->assertArrayHasKey('properties', $feature);
        $this->assertIsArray($feature['properties']);

        $properties = $feature['properties'];
        $this->assertArrayHasKey('clusterName', $properties);
    }
}

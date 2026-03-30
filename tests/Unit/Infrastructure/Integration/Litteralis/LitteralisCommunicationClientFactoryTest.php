<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Integration\Litteralis;

use App\Infrastructure\Integration\Litteralis\LitteralisClient;
use App\Infrastructure\Integration\Litteralis\LitteralisCommunicationClientFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LitteralisCommunicationClientFactoryTest extends TestCase
{
    public function testCreateReturnsClientUsingCommunicationLayer(): void
    {
        $capturedUrl = null;
        $mockResponse = new MockResponse(
            json_encode([
                'features' => [],
                'totalFeatures' => 0,
                'numberMatched' => 0,
            ]),
            ['http_code' => 200],
        );

        $httpClient = new MockHttpClient(function (string $method, string $url) use (&$capturedUrl, $mockResponse) {
            $capturedUrl = $url;

            return $mockResponse;
        }, 'http://testserver');

        $factory = new LitteralisCommunicationClientFactory($httpClient);
        $client = $factory->create('user:pass');

        $this->assertInstanceOf(LitteralisClient::class, $client);

        $client->fetchAllPaginated("mesure ILIKE '%test%'");

        $this->assertNotNull($capturedUrl);
        $query = parse_url((string) $capturedUrl, PHP_URL_QUERY);
        self::assertNotFalse($query);
        parse_str($query, $params);
        self::assertSame('litteralis:LIcommunication', $params['TYPENAME'] ?? null);
    }
}

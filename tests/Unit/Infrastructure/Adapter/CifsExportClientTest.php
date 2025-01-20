<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\CifsExportClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class CifsExportClientTest extends TestCase
{
    private $dialogHttpClient;
    private $client;

    protected function setUp(): void
    {
        $this->dialogHttpClient = new MockHttpClient(baseUri: 'http://testserver');
        $this->client = new CifsExportClient($this->dialogHttpClient);
    }

    public function testGetIncidentsCountZero(): void
    {
        $this->dialogHttpClient->setResponseFactory(function (string $method, string $url, array $options) {
            $this->assertSame('GET', $method);
            $this->assertSame('http://testserver/api/regulations/cifs.xml', $url);

            return new MockResponse('<incidents></incidents>');
        });

        $this->assertSame(0, $this->client->getIncidentsCount());
    }

    public function testGetIncidentsCountSome(): void
    {
        $this->dialogHttpClient->setResponseFactory(function (string $method, string $url, array $options) {
            $this->assertSame('GET', $method);
            $this->assertSame('http://testserver/api/regulations/cifs.xml', $url);

            return new MockResponse('<incidents>
                <incident>example1</incident>
                <incident>example2</incident>
            </incidents>');
        });

        $this->assertSame(2, $this->client->getIncidentsCount());
    }
}

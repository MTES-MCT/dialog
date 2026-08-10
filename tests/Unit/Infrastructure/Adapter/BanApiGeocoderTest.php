<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Adapter;

use App\Infrastructure\Adapter\BanApiGeocoder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BanApiGeocoderTest extends TestCase
{
    public function testFindHouseNumbers(): void
    {
        $body = json_encode([
            'id' => '44195_0137',
            'nomVoie' => 'Route du Grand Brossais',
            'numeros' => [
                ['numero' => 10, 'suffixe' => null],
                ['numero' => 2, 'suffixe' => null],
                ['numero' => 6, 'suffixe' => 'Bis'],
                ['numero' => 1, 'suffixe' => null],
                ['numero' => 6, 'suffixe' => null],
            ],
        ]);
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response], 'https://testserver');

        $geocoder = new BanApiGeocoder($http);

        $this->assertSame(
            ['1', '2', '6', '6bis', '10'],
            $geocoder->findHouseNumbers('44195_0137'),
        );

        $this->assertSame('GET', $response->getRequestMethod());
        $this->assertSame('https://testserver/lookup/44195_0137', $response->getRequestUrl());
    }

    public function testFindHouseNumbersEmptyRoadBanId(): void
    {
        $http = new MockHttpClient([]);
        $geocoder = new BanApiGeocoder($http);

        $this->assertSame([], $geocoder->findHouseNumbers(''));
        $this->assertSame(0, $http->getRequestsCount());
    }

    public function testFindHouseNumbersWithoutNumeros(): void
    {
        $response = new MockResponse(json_encode(['id' => '44195_0137']), ['http_code' => 200]);
        $http = new MockHttpClient([$response]);
        $geocoder = new BanApiGeocoder($http);

        $this->assertSame([], $geocoder->findHouseNumbers('44195_0137'));
    }

    public function testFindHouseNumbersError(): void
    {
        $response = new MockResponse('...', ['http_code' => 500]);
        $http = new MockHttpClient([$response]);
        $geocoder = new BanApiGeocoder($http);

        $this->assertSame([], $geocoder->findHouseNumbers('44195_0137'));
    }

    public function testFindHouseNumbersSkipsNumeroWithoutNumero(): void
    {
        $body = json_encode([
            'id' => '44195_0137',
            'numeros' => [
                ['suffixe' => null],
                ['numero' => 1, 'suffixe' => null],
            ],
        ]);
        $response = new MockResponse($body, ['http_code' => 200]);
        $http = new MockHttpClient([$response]);
        $geocoder = new BanApiGeocoder($http);

        $this->assertSame(['1'], $geocoder->findHouseNumbers('44195_0137'));
    }

    public function testFindHouseNumbersInvalidJson(): void
    {
        $response = new MockResponse('not json', ['http_code' => 200]);
        $http = new MockHttpClient([$response]);
        $geocoder = new BanApiGeocoder($http);

        $this->assertSame([], $geocoder->findHouseNumbers('44195_0137'));
    }
}

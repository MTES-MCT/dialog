<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use App\Application\Exception\GeocodingFailureException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class APIAdresseMockClient extends MockHttpClient
{
    private string $baseUri = 'https://testserver';

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);
        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        if ($method === 'GET' && str_starts_with($url, $this->baseUri . '/search/')) {
            return $this->getSearchMock($options);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method $url");
    }

    private function getSearchMock(array $options): MockResponse
    {
        if (\str_contains($options['query']['q'], 'GEOCODING_FAILURE')) {
            throw new GeocodingFailureException();
        }

        $body = ['features' => [['geometry' => ['coordinates' => [0.4, 44.5]]]]];

        return new MockResponse(
            json_encode($body, JSON_THROW_ON_ERROR),
            ['http_code' => 200]
        );
    }
}

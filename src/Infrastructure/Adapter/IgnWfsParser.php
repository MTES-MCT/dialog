<?php

declare(strict_types=1);

namespace App\Infrastructure\Adapter;

use App\Application\Exception\GeocodingFailureException;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IgnWfsParser
{
    public function __construct(
        private string $ignWfsUrl,
        private HttpClientInterface $ignWfsClient,
    ) {
    }

    public function parse(array $query): array
    {
        $response = $this->ignWfsClient->request('GET', $this->ignWfsUrl, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'query' => array_merge([
                'SERVICE' => 'WFS',
                'REQUEST' => 'GetFeature',
                'VERSION' => '2.0.0',
                'OUTPUTFORMAT' => 'application/json',
            ], $query),
        ]);

        try {
            $body = $response->getContent(throw: true);
        } catch (HttpExceptionInterface $exc) {
            $message = sprintf('invalid response: %s', $exc->getMessage());
            throw new GeocodingFailureException($message);
        }

        try {
            return json_decode($body, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exc) {
            $message = sprintf('invalid json: %s', $exc->getMessage());
            throw new GeocodingFailureException($message);
        }
    }
}

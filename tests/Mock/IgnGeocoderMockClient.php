<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IgnGeocoderMockClient extends MockHttpClient
{
    private string $baseUri = 'https://testserver';
    private array $requests;

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);
        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        $this->requests[] = ['url' => $url, 'options' => $options];

        if (preg_match('/\/geocodage\/completion/', $url)) {
            return new MockResponse($this->getGeocodageCompletionJSON($options['query']['text']), ['http_code' => 200]);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method $url");
    }

    private function getGeocodageCompletionJSON(string $text): string
    {
        if ($text === 'Par') {
            return json_encode([
                'results' => [
                    [
                        'fulltext' => 'Rue du Parc',
                        'x' => 'x1',
                        'y' => 'y1',
                        'kind' => 'street',
                    ],
                    [
                        'fulltext' => 'Paris',
                        'x' => 'x2',
                        'y' => 'y2',
                        'kind' => 'administratif',
                    ],
                ],
            ]);
        }

        return json_encode([
            'results' => [],
        ]);
    }
}

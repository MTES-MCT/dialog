<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ApiOrganizationFetcherMock extends MockHttpClient
{
    private string $baseUri = 'https://testserver';

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);
        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        $results = match ($options['query']['q']) {
            '82050375300015' => ['results' => [0 => ['nom_complet' => 'Fairness']], 'total_results' => 1],
            default => ['results' => [], 'total_results' => 0],
        };

        return new MockResponse(json_encode($results), ['http_code' => 200]);
    }
}

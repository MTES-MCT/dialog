<?php

declare(strict_types=1);

namespace App\Tests\Mock\Litteralis;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class LitteralisMockHttpClient extends MockHttpClient
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
        if ($method === 'GET' && str_starts_with($url, 'https://testserver/maplink/public/wfs')) {
            $content = file_get_contents(__DIR__ . '/LitteralisMockHttpClient.data.json');

            return new MockResponse($content, ['http_code' => 200]);
        }

        throw new \UnexpectedValueException("Mock not implemented: $method $url");
    }
}

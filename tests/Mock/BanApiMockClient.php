<?php

declare(strict_types=1);

namespace App\Tests\Mock;

use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class BanApiMockClient extends MockHttpClient
{
    private string $baseUri = 'https://testserver';

    public function __construct()
    {
        $callback = \Closure::fromCallable([$this, 'handleRequests']);
        parent::__construct($callback, $this->baseUri);
    }

    private function handleRequests(string $method, string $url, array $options): MockResponse
    {
        if ($method === 'GET' && preg_match('#/lookup/(?<id>[^/?]+)#', $url, $matches)) {
            return $this->getLookupMock(rawurldecode($matches['id']));
        }

        throw new \UnexpectedValueException("Mock not implemented: $method $url");
    }

    private function getLookupMock(string $roadBanId): MockResponse
    {
        $numeros = match ($roadBanId) {
            '75104_0092' => [
                ['numero' => 2, 'suffixe' => null],
                ['numero' => 1, 'suffixe' => null],
                ['numero' => 6, 'suffixe' => 'Bis'],
                ['numero' => 10, 'suffixe' => null],
            ],
            default => [],
        };

        return new MockResponse(
            json_encode([
                'id' => $roadBanId,
                'nomVoie' => 'Rue de test',
                'numeros' => $numeros,
            ]),
            ['http_code' => 200],
        );
    }
}
